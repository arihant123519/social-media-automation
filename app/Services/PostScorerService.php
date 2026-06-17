<?php

namespace App\Services;

/**
 * Scores an uploaded post against a trending reference using Google Gemini.
 *
 * Gemini handles video + image natively (no ffmpeg needed).
 * No fallback — if Gemini fails, the error surfaces clearly to the user.
 */
class PostScorerService
{
    private GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * @param  array  $previousAttempts  list of prior attempts (attempt_number, score, summary, previous_suggestions)
     *                                   so the AI can be attempt-aware and avoid repeating feedback
     * @return array{success: bool, score?: int, data?: array, error?: string, provider?: string}
     */
    public function score(
        array $reference,
        string $postType,
        string $absolutePath,
        string $mime,
        string $userCaption = '',
        string $userHashtags = '',
        array $previousAttempts = [],
        int $attemptNumber = 1,
        string $brandVoice = ''
    ): array {
        if (! $this->gemini->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not set.'];
        }

        $isVideo = str_starts_with($mime, 'video/');
        $isImage = str_starts_with($mime, 'image/');
        $framesProvided = $isVideo ? -1 : ($isImage ? 1 : 0);

        $prompt = $this->buildPrompt($reference, $postType, $userCaption, $userHashtags, $framesProvided, $previousAttempts, $attemptNumber, $brandVoice);
        $result = $this->gemini->generate($prompt, $absolutePath, $mime);

        if (! $result['success']) {
            return ['success' => false, 'error' => 'Gemini: ' . ($result['error'] ?? 'unknown')];
        }

        $data = $result['data'];
        if (! isset($data['total_score'])) {
            return ['success' => false, 'error' => 'Gemini response missing total_score.'];
        }

        return [
            'success'  => true,
            'score'    => max(0, min(100, (int) $data['total_score'])),
            'data'     => $data,
            'provider' => 'gemini',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PROMPT
    //  $framesProvided:  -1 = native video, 0 = no media, 1 = single image
    // ─────────────────────────────────────────────────────────────
    private function buildPrompt(array $ref, string $postType, string $caption, string $hashtags, int $framesProvided = 0, array $previousAttempts = [], int $attemptNumber = 1, string $brandVoice = ''): string
    {
        $refTitle    = $ref['title']    ?? '(unknown)';
        $refDesc     = mb_substr((string) ($ref['description'] ?? $ref['caption'] ?? ''), 0, 400);
        $refViews    = $ref['views']    ?? $ref['likes'] ?? '?';
        $refVelocity = $ref['views_per_day'] ?? null;
        $refTags     = is_array($ref['hashtags'] ?? null) ? implode(' ', $ref['hashtags']) : '';
        $refChannel  = $ref['channel']  ?? '';
        $refDaysOld  = $ref['days_old'] ?? null;
        $typeLabel   = $this->typeLabel($postType);

        // Standalone mode: posts created from the AI Studio tools may skip the
        // trending comparison. With no reference we score the post on its own
        // merits against best-practice benchmarks (same JSON shape + same gate).
        $standalone = empty($ref);
        $platform   = in_array($postType, ['long_video', 'short_video'], true) ? 'YouTube' : 'Instagram';

        $velocityLine = $refVelocity ? "- Views/day (momentum): {$refVelocity}\n" : '';
        $ageLine      = $refDaysOld  ? "- Age: {$refDaysOld} days old\n" : '';

        $mediaLine = match (true) {
            $framesProvided === -1 => "The user's full video file is attached. Watch it end-to-end: judge hook (first 3 sec), pacing, visuals, audio cues if audible, and CTA at the end.",
            $framesProvided === 1  => "You will be shown 1 image attached after this prompt (the user's photo upload). Analyze it visually.",
            default                => "No visual media is attached — score from text/metadata only. Note this limitation in feedback.",
        };

        // Build a previous-attempts context block so the AI is attempt-aware
        $previousBlock = '';
        if (! empty($previousAttempts)) {
            $lines = [];
            foreach ($previousAttempts as $pa) {
                $n     = $pa['attempt_number']      ?? '?';
                $sc    = $pa['score']               ?? '?';
                $sum   = $pa['summary']             ?? '';
                $prev  = $pa['previous_suggestions']?? [];
                $errs  = $pa['spell_errors_count']  ?? 0;
                $prevText = is_array($prev) && $prev
                    ? '- Previously flagged: ' . implode(' | ', array_map(fn($s) => trim((string) $s), array_slice($prev, 0, 4)))
                    : '';
                $lines[] = "V{$n} scored {$sc}/100 (spelling errors: {$errs}). Summary: {$sum}\n  {$prevText}";
            }
            $prevList = implode("\n", $lines);
            $previousBlock = <<<PREV

PREVIOUS ATTEMPTS BY THIS SAME USER (chronological):
{$prevList}

THIS IS ATTEMPT #{$attemptNumber}. CRITICAL RULES FOR THIS ATTEMPT:
- DO NOT repeat the same generic suggestions that appeared in previous attempts. The user has read them.
- For each previously-flagged issue, INSPECT this new upload and tell me concretely whether the user FIXED it, IGNORED it, or PARTIALLY fixed it. Quote what they did.
- If a previous issue is now resolved, increase that parameter's score and SAY SO in the feedback ("Hook improved — now starts with a question vs. the bland statement in V1").
- If the same issue persists, score it lower and give a DIFFERENT, more specific suggestion than before (not "add a hook" again — say WHAT hook, WHICH first line, WHICH technique).
- Quick_wins must be FRESH for this attempt — not boilerplate. If you can't find anything new, say "Your work is solid; focus on X micro-detail" instead of recycling.
PREV;
        }

        // ── Mode-specific prompt fragments (standalone vs. comparison) ──
        $taskLine = $standalone
            ? "TASK: Score the user's uploaded {$typeLabel} on its own merits as a high-performing {$platform} post, judged against best-practice benchmarks for this format. Return a Health Score out of 100."
            : "TASK: Compare the user's uploaded {$typeLabel} against a high-performing trending reference, and return a Health Score out of 100.";

        $bothSidesBlock = $standalone
            ? <<<TXT
For EACH scoring parameter you MUST explain BOTH sides:
- `reference_says`: A concise BEST-PRACTICE BENCHMARK for this parameter on a high-performing {$typeLabel} (e.g. "Strong reels hook the viewer in the first 3 seconds"). This is a general standard, NOT a specific competitor.
- `your_post_says`: What the user's post does for this parameter (from their caption/hashtags/attached media).
- `feedback`: How the user's post measures up to the benchmark and what is missing.
- `suggestions`: 1-3 concrete fixes the user should make.
TXT
            : <<<TXT
For EACH scoring parameter you MUST explain BOTH sides:
- `reference_says`: What the trending reference does for this parameter (concrete details from its title/description/hashtags).
- `your_post_says`: What the user's post does for this parameter (from their caption/hashtags/attached media).
- `feedback`: How they compare and what's missing.
- `suggestions`: 1-3 concrete fixes the user should make.
TXT;

        $referenceBlock = $standalone
            ? "STANDALONE SCORING — no competitor reference was provided. Do NOT invent a competitor. Score against best-practice benchmarks for a high-performing {$typeLabel} and fill each `reference_says` with the relevant benchmark."
            : <<<TXT
TRENDING REFERENCE
- Title: "{$refTitle}"
- Channel/Creator: "{$refChannel}"
- Description excerpt: "{$refDesc}"
- Total views/likes: {$refViews}
{$velocityLine}{$ageLine}- Hashtags used: {$refTags}
TXT;

        return \App\Models\Prompt::render('post.scorer', [
            'brandVoice'     => $brandVoice,
            'taskLine'       => $taskLine,
            'mediaLine'      => $mediaLine,
            'previousBlock'  => $previousBlock,
            'bothSidesBlock' => $bothSidesBlock,
            'referenceBlock' => $referenceBlock,
            'caption'        => $caption,
            'hashtags'       => $hashtags,
            'typeLabel'      => $typeLabel,
        ]);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'long_video'  => 'YouTube long video',
            'short_video' => 'YouTube short',
            'reels'       => 'Instagram Reel',
            'photo'       => 'Instagram photo',
            'story'       => 'Instagram story',
            default       => 'social media post',
        };
    }
}
