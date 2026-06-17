<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * AI Studio #4 — Reel Analyzer.
 *
 * Paste a reel link → AI breaks down its target audience, topic, hook, caption
 * style and hashtag strategy. Optionally upload your own video to apply the same
 * strategy: you get optimized captions + hashtag recommendations and a match
 * score (0-100) showing how well your content fits the target audience.
 *
 * The uploaded video is sent to Gemini for native analysis and then deleted —
 * nothing about the analysis is written to the database.
 */
class ReelAnalyzerController extends Controller
{
    public function index()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);

        return view('tools.reel-analyzer', compact('clients'));
    }

    public function analyze(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'reel_url'  => 'required|url|max:500',
            'notes'     => 'nullable|string|max:500',
            'video'     => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska|max:204800', // 200MB
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.']);
        }

        $client     = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $notes      = trim((string) ($data['notes'] ?? ''));

        // The user's own video is optional. When present we hand the real bytes to
        // Gemini so it can score the match against the reference reel's audience.
        $absolutePath = null;
        $mime         = '';
        $relative     = null;
        if ($request->hasFile('video')) {
            $file     = $request->file('video');
            $mime     = $file->getMimeType() ?? 'video/mp4';
            $ext      = $file->getClientOriginalExtension() ?: 'mp4';
            $relative = $file->storeAs('reel-analyzer/' . uniqid('clip_', true), "upload.{$ext}", 'public');
            $absolutePath = Storage::disk('public')->path($relative);
        }

        $hasOwnVideo = $absolutePath !== null;
        $videoClause = $hasOwnVideo
            ? "The user has ALSO attached THEIR OWN video file after this prompt. Watch it end-to-end and judge how well it matches the reference reel's winning strategy and target audience. Fill the \"your_video\" object and \"match\" object accordingly."
            : "The user did NOT attach their own video. Set \"your_video\" and \"match\" to null. Still produce a complete reference analysis + optimized recommendations they can apply.";

        $notesClause = $notes !== '' ? "User context about the reel: \"{$notes}\"." : '';

        $prompt = \App\Models\Prompt::render('reel.analyzer', [
            'brandBlock'  => $brandBlock,
            'reelUrl'     => $data['reel_url'],
            'notesClause' => $notesClause,
            'videoClause' => $videoClause,
        ]);

        $r = $gemini->generate($prompt, $absolutePath, $mime);

        // Clean up the uploaded clip — we never keep user media around.
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
            // best-effort: remove the now-empty parent dir
            @rmdir(dirname(Storage::disk('public')->path($relative)));
        }

        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            return response()->json(['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown error')]);
        }

        return response()->json(['success' => true, 'data' => $r['data'], 'had_video' => $hasOwnVideo]);
    }
}
