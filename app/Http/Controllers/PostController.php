<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Post;
use App\Models\PostAttempt;
use App\Services\GeminiService;
use App\Services\InstagramService;
use App\Services\PostScorerService;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    private const MAX_ATTEMPTS    = 3;

    /**
     * Allowed post_type per scope.
     */
    private const TYPES = [
        0 => ['long_video', 'short_video'],     // YouTube
        1 => ['reels', 'photo', 'story'],       // Instagram
    ];

    // ─────────────────────────────────────────────
    //  POST CREATOR PAGE
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();

        // Pre-fill from calendar slot (passed as query string)
        $prefill = [
            'client_id'       => $request->query('client_id'),
            'scope'           => $request->query('scope'),
            'post_type'       => $request->query('post_type'),
            'scheduled_date'  => $request->query('scheduled_date'),
            'client_scope_id' => $request->query('client_scope_id'),
        ];

        return view('post.post', compact('clients', 'prefill'));
    }

    // ─────────────────────────────────────────────
    //  STEP 1: FETCH TRENDING POSTS
    // ─────────────────────────────────────────────
    public function trending(Request $request, YouTubeService $yt, InstagramService $ig)
    {
        $data = $request->validate([
            'keyword'   => 'required|string|max:200',
            'scope'     => 'required|in:0,1',
            'post_type' => 'required|string|max:30',
            'client_id' => 'required|exists:clients,id',
        ]);

        $scope = (int) $data['scope'];
        if (! in_array($data['post_type'], self::TYPES[$scope], true)) {
            return response()->json(['success' => false, 'error' => 'Invalid post_type for scope.'], 422);
        }

        if ($scope === 0) {
            $result = $yt->trending($data['keyword'], $data['post_type']);
            return response()->json($result);
        }

        // Instagram
        $result = $ig->trending($data['keyword'], $data['post_type']);

        // Fallback to AI-generated trending if IG not configured
        if (! ($result['success'] ?? false) && ($result['fallback'] ?? false)) {
            $result = $this->aiTrendingFallback($data['keyword'], $data['post_type']);
        }

        return response()->json($result);
    }

    // ─────────────────────────────────────────────
    //  STEP 1.5: KEYWORD INSPIRATION (text-only AI, no file)
    //  Returns hooks, hashtag mix, script outline, best time
    // ─────────────────────────────────────────────
    public function inspiration(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'keyword'   => 'required|string|max:200',
            'scope'     => 'required|in:0,1',
            'post_type' => 'required|string|max:30',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        // #12 — per-client brand voice context (empty string when not set)
        $brandBlock = '';
        if (! empty($data['client_id'])) {
            $brandBlock = optional(Client::find($data['client_id']))->brandVoiceBlock() ?? '';
        }

        $platform = $data['scope'] == 0 ? 'YouTube' : 'Instagram';
        $typeLabel = match ($data['post_type']) {
            'long_video'  => 'YouTube long videos',
            'short_video' => 'YouTube Shorts',
            'reels'       => 'Instagram Reels',
            'photo'       => 'Instagram photo posts',
            'story'       => 'Instagram Stories',
            default       => "{$platform} posts",
        };

        $prompt = \App\Models\Prompt::render('post.inspiration', [
            'brandBlock' => $brandBlock,
            'typeLabel'  => $typeLabel,
            'keyword'    => $data['keyword'],
            'platform'   => $platform,
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set in .env.']);
        }

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false)) {
            return response()->json(['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown')]);
        }
        return response()->json(['success' => true, 'data' => $r['data'], 'provider' => 'gemini']);
    }

    // ─────────────────────────────────────────────
    //  STEP 2: START A POST (creates Post row, attempt 1 not yet uploaded)
    // ─────────────────────────────────────────────
    public function start(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required|exists:clients,id',
            'scope'             => 'required|in:0,1',
            'post_type'         => 'required|string|max:30',
            'keyword'           => 'required|string|max:200',
            // Optional: posts created from the AI Studio tools skip the trending
            // comparison and are scored standalone, so these may be absent.
            'trending_ref_id'   => 'nullable|string|max:100',
            'trending_ref_meta' => 'nullable|array',
            'scheduled_date'    => 'nullable|date',
            'client_scope_id'   => 'nullable|exists:client_scopes,id',
        ]);

        // If a calendar slot is provided, ensure a PostLog row exists for it
        $postLogId = null;
        if (! empty($data['scheduled_date']) && ! empty($data['client_scope_id'])) {
            $log = \App\Models\PostLog::firstOrCreate(
                [
                    'client_scope_id' => $data['client_scope_id'],
                    'post_type'       => $data['post_type'],
                    'scheduled_date'  => $data['scheduled_date'],
                ],
                [
                    'client_id' => $data['client_id'],
                    'scope'     => (int) $data['scope'],
                    'status'    => 'pending',
                ]
            );
            $postLogId = $log->id;
        }

        $post = Post::create([
            'client_id'         => $data['client_id'],
            'user_id'           => Auth::id(),
            'scope'             => (int) $data['scope'],
            'post_type'         => $data['post_type'],
            'keyword'           => $data['keyword'],
            'scheduled_date'    => $data['scheduled_date'] ?? null,
            'client_scope_id'   => $data['client_scope_id'] ?? null,
            'post_log_id'       => $postLogId,
            'trending_ref_id'   => $data['trending_ref_id'] ?? null,
            'trending_ref_meta' => $data['trending_ref_meta'] ?? null,
        ]);

        return response()->json(['success' => true, 'post_id' => $post->id]);
    }

    // ─────────────────────────────────────────────
    //  STEP 3: UPLOAD + SCORE  (called up to 3 times)
    // ─────────────────────────────────────────────
    public function upload(Request $request, PostScorerService $scorer)
    {
        $data = $request->validate([
            'post_id'  => 'required|exists:posts,id',
            'file'     => 'required|file|max:204800', // 200MB
            'caption'  => 'nullable|string|max:2500',
            'hashtags' => 'nullable|string|max:600',
        ]);

        /** @var Post $post */
        $post = Post::with(['attempts', 'client'])->findOrFail($data['post_id']);

        // Auth check: only the owner can upload
        if ($post->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Not your post.'], 403);
        }

        if ($post->final_status === 'approved') {
            return response()->json(['success' => false, 'error' => 'This post is already approved.'], 422);
        }

        $attemptCount = $post->attempts->count();
        if ($attemptCount >= self::MAX_ATTEMPTS) {
            return response()->json(['success' => false, 'error' => 'Maximum 3 attempts reached.'], 422);
        }

        $attemptNumber = $attemptCount + 1;

        $file = $request->file('file');
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        // Validate file type matches post_type expectation
        $allowedMime = $this->allowedMimesFor($post->post_type);
        if (! in_array($mime, $allowedMime, true) && ! $this->mimeMatchesPrefix($mime, $post->post_type)) {
            return response()->json([
                'success' => false,
                'error'   => "Wrong file type for {$post->post_type}. Got {$mime}.",
            ], 422);
        }

        // Store on public disk → storage/app/public/posts/{post_id}/attempt{n}.ext
        $ext      = $file->getClientOriginalExtension() ?: 'bin';
        $relative = $file->storeAs("posts/{$post->id}", "attempt{$attemptNumber}.{$ext}", 'public');
        $absolute = Storage::disk('public')->path($relative);

        // Collect previous attempts so scorer can give attempt-aware feedback
        $previousAttempts = $post->attempts->map(function ($a) {
            $fb = is_array($a->ai_feedback) ? $a->ai_feedback : [];
            return [
                'attempt_number'        => $a->attempt_number,
                'score'                 => $a->score,
                'summary'               => $fb['summary'] ?? null,
                'previous_suggestions'  => array_slice((array) ($a->suggestions ?? []), 0, 5),
                'spell_errors_count'    => count($fb['parameters']['spelling_grammar']['errors'] ?? []),
            ];
        })->values()->all();

        // Score via Gemini
        $result = $scorer->score(
            $post->trending_ref_meta ?? [],
            $post->post_type,
            $absolute,
            $mime,
            (string) $request->input('caption', ''),
            (string) $request->input('hashtags', ''),
            $previousAttempts,
            $attemptNumber,
            $post->client?->brandVoiceBlock() ?? ''
        );

        $score    = $result['success'] ? (int) $result['score'] : 0;
        $feedback = $result['data']    ?? null;
        $errorMsg = $result['error']   ?? null;

        // ── Drop hallucinated spelling errors ──
        // Gemini occasionally flags a word as misspelled when the word doesn't
        // actually appear in the user's caption (e.g. it "sees" Febuary when
        // the caption clearly has February). Verify each claimed error against
        // the real caption text — if the misspelled token isn't present,
        // it's a false positive and we drop it.
        $userCaption = (string) $request->input('caption', '');
        if (is_array($feedback) && isset($feedback['parameters']['spelling_grammar']['errors'])) {
            $rawErrors = $feedback['parameters']['spelling_grammar']['errors'];
            if (is_array($rawErrors)) {
                $feedback['parameters']['spelling_grammar']['errors'] =
                    $this->filterHallucinatedSpellingErrors($rawErrors, $userCaption);
            }
        }

        $suggestions = [];
        if (is_array($feedback)) {
            $suggestions = array_merge(
                $feedback['quick_wins'] ?? [],
                collect($feedback['parameters'] ?? [])
                    ->flatMap(fn ($p) => is_array($p['suggestions'] ?? null) ? $p['suggestions'] : [])
                    ->all()
            );
        }

        $attempt = PostAttempt::create([
            'post_id'        => $post->id,
            'attempt_number' => $attemptNumber,
            'file_path'      => $relative,
            'mime'           => $mime,
            'file_size'      => $file->getSize() ?: 0,
            'caption'        => (string) $request->input('caption', ''),
            'hashtags'       => (string) $request->input('hashtags', ''),
            'score'          => $score,
            'ai_feedback'    => $feedback,
            'suggestions'    => array_values(array_unique(array_filter($suggestions))),
        ]);

        // Update Post aggregates
        $post->best_score = max($post->best_score, $score);

        // Approval requires BOTH gates: score >= threshold AND zero spelling errors
        $spellErrorsThisAttempt = is_array($feedback)
            ? count($feedback['parameters']['spelling_grammar']['errors'] ?? [])
            : 0;
        $bothGatesPass = $score >= \App\Services\PostPublisher::approvalScore() && $spellErrorsThisAttempt === 0;

        if ($bothGatesPass) {
            $post->final_status = 'approved';
        } elseif ($attemptNumber >= self::MAX_ATTEMPTS) {
            $post->final_status = 'max_attempts';
        }
        // else: stays 'in_progress' → re-upload remains available

        $post->save();

        return response()->json([
            'success'             => $result['success'],
            'error'               => $errorMsg,
            'post_id'             => $post->id,
            'attempt_number'      => $attemptNumber,
            'attempts_left'       => self::MAX_ATTEMPTS - $attemptNumber,
            'score'               => $score,
            'spell_errors_count'  => $spellErrorsThisAttempt,
            'score_gate_pass'     => $score >= \App\Services\PostPublisher::approvalScore(),
            'spell_gate_pass'     => $spellErrorsThisAttempt === 0,
            'feedback'            => $feedback,
            'suggestions'         => $attempt->suggestions,
            'approved'            => $post->final_status === 'approved',
            'final_status'        => $post->final_status,
            'best_score'          => $post->best_score,
            'all_attempts'   => $post->attempts()->get()->map(function ($a) {
                $fb       = is_array($a->ai_feedback) ? $a->ai_feedback : [];
                $spellErr = $fb['parameters']['spelling_grammar']['errors'] ?? [];
                $topIssues = array_slice(array_filter([
                    ...($fb['quick_wins'] ?? []),
                ]), 0, 2);

                return [
                    'attempt_number'      => $a->attempt_number,
                    'score'               => $a->score,
                    'grade'               => $fb['grade'] ?? null,
                    'file_url'            => '/storage/' . ltrim((string) $a->file_path, '/'),
                    'mime'                => $a->mime,
                    'suggestions'         => $a->suggestions,
                    'spell_errors_count'  => is_array($spellErr) ? count($spellErr) : 0,
                    'top_issues'          => $topIssues,
                ];
            })->values(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  STEP 4: DOWNLOAD APPROVED BUNDLE (ZIP)
    //  Contents: media file + caption.txt + hashtags.txt + report.txt
    //  Same data also used by Phase 4 auto-publish flow.
    // ─────────────────────────────────────────────
    /**
     * Drafts list — posts the user started but hasn't finished (not approved,
     * not published, not maxed-out). Lets them resume uploading attempts.
     */
    public function drafts()
    {
        // Auto-demote approved posts whose score no longer meets the current
        // approval threshold (e.g. threshold was raised after they were approved).
        // Moves them back to in_progress so the user can upload another attempt.
        // Scheduled/publishing/published posts are left alone.
        $threshold = \App\Services\PostPublisher::approvalScore();
        Post::where('user_id', Auth::id())
            ->where('final_status', 'approved')
            ->where('best_score', '<', $threshold)
            ->whereNotIn('publish_status', ['published', 'publishing', 'scheduled'])
            ->update(['final_status' => 'in_progress']);

        $base = Post::with(['client:id,name', 'attempts'])
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at');

        $drafts = (clone $base)
            ->where(function ($q) {
                $q->whereNull('final_status')->orWhere('final_status', 'in_progress');
            })
            ->whereNotIn('publish_status', ['published', 'publishing'])
            ->get();

        $approved = (clone $base)
            ->where('final_status', 'approved')
            ->where(function ($q) {
                $q->whereNull('publish_status')
                  ->orWhereNotIn('publish_status', ['published', 'publishing']);
            })
            ->get();

        return view('post.drafts', compact('drafts', 'approved'));
    }

    /**
     * Edit caption + hashtags of an approved (or otherwise scored) post's
     * winning attempt — used when the user wants to tweak text before
     * publishing, without redoing the AI score.
     */
    public function editCaption(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $winner = $post->attempts()->reorder()
            ->orderByDesc('score')->orderByDesc('attempt_number')
            ->first();

        if (! $winner) {
            return redirect()->route('posts.drafts')->with('error', 'No scored attempt to edit yet.');
        }

        $post->load('client:id,name');
        return view('post.edit-caption', ['post' => $post, 'winner' => $winner]);
    }

    /**
     * Pre-publish preview payload — returns the media + caption + hashtags
     * that will actually go live, used by the "preview before publish" modal.
     */
    public function preview(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $winner = $post->attempts()->reorder()
            ->orderByDesc('score')->orderByDesc('attempt_number')
            ->first();

        if (! $winner) {
            return response()->json(['success' => false, 'error' => 'No scored attempt found.'], 404);
        }

        return response()->json([
            'success'       => true,
            'client_name'   => $post->client->name ?? null,
            'scope'         => $post->scope,
            'post_type'     => $post->post_type,
            // Relative URL — resolves to request host (works on localhost AND on
            // the live server). Avoids APP_URL pointing to a stale/unreachable tunnel.
            'media_url'     => '/storage/' . ltrim((string) $winner->file_path, '/'),
            'mime'          => $winner->mime,
            'caption'       => (string) $winner->caption,
            'hashtags'      => (string) $winner->hashtags,
            'score'         => $winner->score,
            'attempt'       => $winner->attempt_number,
            'publish_status'=> $post->publish_status,
        ]);
    }

    public function updateCaption(Request $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        if (in_array($post->publish_status, ['published', 'publishing'], true)) {
            return back()->with('error', 'Cannot edit a published/publishing post.');
        }

        $data = $request->validate([
            'caption'  => 'nullable|string|max:2500',
            'hashtags' => 'nullable|string|max:600',
        ]);

        $winner = $post->attempts()->reorder()
            ->orderByDesc('score')->orderByDesc('attempt_number')
            ->first();

        if (! $winner) {
            return back()->with('error', 'No attempt found.');
        }

        $winner->update([
            'caption'  => $data['caption']  ?? null,
            'hashtags' => $data['hashtags'] ?? null,
        ]);

        return redirect()->route('posts.drafts')->with('success', 'Caption & hashtags updated.');
    }

    /**
     * Standalone "continue uploading" page for an existing draft post.
     * Reuses the existing /posts/upload endpoint; doesn't touch the main wizard.
     */
    public function resume(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $post->load(['client:id,name', 'attempts']);
        return view('post.resume', ['post' => $post, 'maxAttempts' => self::MAX_ATTEMPTS]);
    }

    /**
     * Delete a draft (only owner, only unpublished drafts).
     */
    public function destroyDraft(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        if (in_array($post->publish_status, ['published', 'publishing'], true)) {
            return back()->with('error', 'Cannot delete a published/publishing post.');
        }

        // Remove uploaded media for each attempt.
        foreach ($post->attempts as $a) {
            if ($a->file_path && Storage::disk('public')->exists($a->file_path)) {
                Storage::disk('public')->delete($a->file_path);
            }
        }
        $post->attempts()->delete();
        $post->delete();

        return redirect()->route('posts.drafts')->with('success', 'Draft deleted.');
    }

    /**
     * Drop AI spelling-error entries whose flagged token doesn't actually
     * appear in the user's caption — pure LLM hallucinations.
     */
    private function filterHallucinatedSpellingErrors(array $errors, string $caption): array
    {
        if ($caption === '') {
            // Nothing to validate against; trust the model.
            return array_values($errors);
        }
        $captionLower = mb_strtolower($caption);

        return array_values(array_filter($errors, function ($entry) use ($captionLower) {
            // Try to find the "wrong" token in different shapes of error entries.
            $text = '';
            if (is_string($entry)) {
                $text = $entry;
            } elseif (is_array($entry)) {
                $text = (string) ($entry['wrong'] ?? $entry['word'] ?? $entry['incorrect'] ?? $entry['original'] ?? json_encode($entry));
            }

            // First quoted token wins: 'Febuary' → 'February'
            if (preg_match('/[\'"`]([\p{L}\p{N}\']+)[\'"`]/u', $text, $m)) {
                $word = mb_strtolower($m[1]);
            } elseif (preg_match('/\p{L}{3,}/u', $text, $m)) {
                $word = mb_strtolower($m[0]);
            } else {
                return true; // unparseable — keep
            }

            // If the supposedly-misspelled word isn't in the caption, it's a
            // hallucination — drop. Otherwise keep (genuine error).
            return mb_strpos($captionLower, $word) !== false;
        }));
    }

    public function download(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        if ($post->final_status !== 'approved' || $post->best_score < \App\Services\PostPublisher::approvalScore()) {
            abort(403, 'Post not yet approved.');
        }

        // reorder() is CRITICAL — Post::attempts() relationship has a default
        // orderBy('attempt_number') that would otherwise win and return V1.
        $best = $post->attempts()
            ->reorder()
            ->orderByDesc('score')
            ->orderByDesc('attempt_number')
            ->first();
        if (! $best || ! Storage::disk('public')->exists($best->file_path)) {
            abort(404);
        }

        $absoluteMedia = Storage::disk('public')->path($best->file_path);
        $mediaExt      = pathinfo($best->file_path, PATHINFO_EXTENSION) ?: 'bin';

        $tmpZip = tempnam(sys_get_temp_dir(), 'pkg_') . '.zip';
        $zip    = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP.');
        }

        $zip->addFile($absoluteMedia, "media.{$mediaExt}");
        $zip->addFromString('caption.txt',  $this->captionFileContents($best));
        $zip->addFromString('hashtags.txt', $this->hashtagsFileContents($best));
        $zip->addFromString('report.txt',   $this->reportFileContents($post, $best));
        $zip->close();

        $name = "approved_post_{$post->id}_score{$best->score}.zip";

        return response()
            ->download($tmpZip, $name)
            ->deleteFileAfterSend(true);
    }

    private function captionFileContents(PostAttempt $best): string
    {
        $caption = trim((string) $best->caption);
        if ($caption === '') {
            $caption = '(no caption was submitted for this attempt)';
        }
        return $caption . "\n";
    }

    private function hashtagsFileContents(PostAttempt $best): string
    {
        $raw = (string) $best->hashtags;
        preg_match_all('/#\w+/u', $raw, $m);
        $tags = array_values(array_unique($m[0] ?? []));
        if (empty($tags)) {
            // Try fallback: maybe user pasted tags without # — split on whitespace and prefix
            $alt = array_filter(array_map('trim', preg_split('/\s+/', $raw)));
            $tags = array_values(array_unique(array_map(
                fn ($t) => str_starts_with($t, '#') ? $t : '#' . $t,
                $alt
            )));
        }
        return $tags
            ? implode("\n", $tags) . "\n\n----- single-line copy -----\n" . implode(' ', $tags) . "\n"
            : "(no hashtags were submitted)\n";
    }

    private function reportFileContents(Post $post, PostAttempt $winner): string
    {
        $ref       = $post->trending_ref_meta ?? [];
        $refTitle  = $ref['title']   ?? '(unknown)';
        $refChan   = $ref['channel'] ?? '';
        $refViews  = $ref['views']   ?? null;

        $fb        = is_array($winner->ai_feedback) ? $winner->ai_feedback : [];
        $summary   = $fb['summary']           ?? '';
        $params    = $fb['parameters']        ?? [];
        $autoFix   = $fb['parameters']['spelling_grammar']['auto_fix'] ?? '';
        $recs      = $fb['recommendations']   ?? [];
        $hooks     = $recs['alternative_hooks']  ?? [];
        $capVars   = $recs['caption_variants']   ?? [];
        $bestTime  = $recs['best_time_to_post']  ?? '';
        $reelIdeas = $recs['new_reel_ideas']     ?? [];

        $lines = [];
        $lines[] = "═══════════════════════════════════════════════════════════";
        $lines[] = "APPROVED POST REPORT";
        $lines[] = "═══════════════════════════════════════════════════════════";
        $lines[] = "Post ID:        #{$post->id}";
        $lines[] = "Approved At:    " . ($post->updated_at?->format('Y-m-d H:i') ?? 'now');
        $lines[] = "Final Score:    {$winner->score}/100  (winner: V{$winner->attempt_number})";
        $lines[] = "Best Score:     {$post->best_score}/100";
        $lines[] = "Platform:       " . ($post->scope == 0 ? 'YouTube' : 'Instagram') . " / {$post->post_type}";
        $lines[] = "Keyword:        {$post->keyword}";
        $lines[] = '';
        $lines[] = "── TRENDING REFERENCE ─────────────────────────────────────";
        $lines[] = "Title:    {$refTitle}";
        $lines[] = "Channel:  {$refChan}";
        if ($refViews) $lines[] = "Views:    " . number_format((int) $refViews);
        $lines[] = '';
        $lines[] = "── AI SUMMARY ─────────────────────────────────────────────";
        $lines[] = wordwrap($summary, 70);
        $lines[] = '';
        $lines[] = "── PARAMETER SCORES ───────────────────────────────────────";
        foreach ($params as $p) {
            $label = str_pad((string) ($p['label'] ?? ''), 22);
            $score = ($p['score'] ?? 0) . '/' . ($p['max'] ?? 0);
            $lines[] = "  {$label} {$score}";
        }
        $lines[] = '';
        $lines[] = "── ATTEMPTS HISTORY ───────────────────────────────────────";
        foreach ($post->attempts()->orderBy('attempt_number')->get() as $a) {
            $tag = $a->id === $winner->id ? '  <-- WINNER' : '';
            $lines[] = "  V{$a->attempt_number}: {$a->score}/100{$tag}";
        }
        $lines[] = '';

        if ($autoFix) {
            $lines[] = "── AI AUTO-FIXED CAPTION ──────────────────────────────────";
            $lines[] = wordwrap((string) $autoFix, 70);
            $lines[] = '';
        }

        if ($hooks) {
            $lines[] = "── ALTERNATIVE HOOKS ──────────────────────────────────────";
            foreach ($hooks as $i => $h) {
                $type = $h['type'] ?? '';
                $text = $h['text'] ?? '';
                $lines[] = '  ' . ($i + 1) . ". [{$type}] {$text}";
            }
            $lines[] = '';
        }

        if ($capVars) {
            $lines[] = "── ALTERNATIVE CAPTION VARIANTS ───────────────────────────";
            foreach ($capVars as $i => $c) {
                $lines[] = '  ' . ($i + 1) . '. ' . wordwrap((string) $c, 65, "\n     ");
            }
            $lines[] = '';
        }

        if ($bestTime) {
            $lines[] = "── BEST TIME TO POST ──────────────────────────────────────";
            $lines[] = "  {$bestTime}";
            $lines[] = '';
        }

        if ($reelIdeas) {
            $lines[] = "── FUTURE REEL IDEAS ──────────────────────────────────────";
            foreach ($reelIdeas as $i => $idea) {
                $lines[] = '  ' . ($i + 1) . '. ' . ($idea['title'] ?? '');
                if (! empty($idea['script_outline'])) {
                    $lines[] = '     Script: ' . wordwrap((string) $idea['script_outline'], 60, "\n             ");
                }
            }
            $lines[] = '';
        }

        $lines[] = "═══════════════════════════════════════════════════════════";
        $lines[] = "Generated by team-automation Post Creator";
        $lines[] = "═══════════════════════════════════════════════════════════";

        return implode("\n", $lines) . "\n";
    }

    // ─────────────────────────────────────────────
    //  AI-GENERATED IG TRENDING FALLBACK
    // ─────────────────────────────────────────────
    private function aiTrendingFallback(string $keyword, string $postType): array
    {
        $gemini = app(GeminiService::class);
        if (! $gemini->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not set — cannot generate fallback trending list.'];
        }

        $label = match ($postType) {
            'reels' => 'Instagram Reels',
            'photo' => 'Instagram photo posts',
            'story' => 'Instagram Stories',
            default => 'Instagram posts',
        };

        // Media type the user will upload for this post type — keeps the grid consistent
        // (photo → image idea, reels → video idea).
        $mediaType = match ($postType) {
            'photo' => 'IMAGE',
            'story' => 'IMAGE',
            default => 'VIDEO',   // reels
        };
        $mediaWord = $mediaType === 'IMAGE' ? 'photo/image' : 'short video/reel';

        $prompt = \App\Models\Prompt::render('post.trending', [
            'label'     => $label,
            'keyword'   => $keyword,
            'mediaWord' => $mediaWord,
            'mediaType' => $mediaType,
        ]);

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false)) {
            return ['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown')];
        }

        $items = $r['data']['items'] ?? (is_array($r['data']) && isset($r['data'][0]) ? $r['data'] : null);
        if (! is_array($items)) {
            return ['success' => false, 'error' => 'Gemini returned invalid trending list.'];
        }

        // Force the correct media_type so the grid never shows a video for a photo post
        $items = array_map(function ($it) use ($mediaType) {
            $it['media_type'] = $mediaType;
            return $it;
        }, $items);

        return ['success' => true, 'items' => $items, 'ai_generated' => true];
    }

    private function allowedMimesFor(string $postType): array
    {
        return match ($postType) {
            'long_video', 'short_video', 'reels' => [
                'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska',
            ],
            'photo' => ['image/jpeg', 'image/png', 'image/webp'],
            'story' => ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'],
            default => [],
        };
    }

    private function mimeMatchesPrefix(string $mime, string $postType): bool
    {
        if (in_array($postType, ['long_video', 'short_video', 'reels'], true)) {
            return str_starts_with($mime, 'video/');
        }
        if ($postType === 'photo') {
            return str_starts_with($mime, 'image/');
        }
        if ($postType === 'story') {
            return str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/');
        }
        return false;
    }
}
