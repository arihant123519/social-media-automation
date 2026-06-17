<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\GeminiService;
use Illuminate\Http\Request;

/**
 * AI Studio #3 — Multi-Format Caption Engine.
 *
 * One Gemini call → three caption variants for the same post:
 *   - Long-form  (Instagram feed)
 *   - Short punchy (Stories)
 *   - Question hook (drives comments)
 * The SMO exec picks one. Nothing is stored in the database.
 */
class MultiCaptionController extends Controller
{
    public function index()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);

        return view('tools.captions-multi', compact('clients'));
    }

    public function generate(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'topic'     => 'required|string|max:400',
            'post_type' => 'nullable|string|max:40',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.']);
        }

        $client     = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $postType   = $data['post_type'] ?: 'reel';

        $prompt = \App\Models\Prompt::render('captions.multi', [
            'brandBlock' => $brandBlock,
            'topic'      => $data['topic'],
            'postType'   => $postType,
        ]);

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            return response()->json(['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown error')]);
        }

        return response()->json(['success' => true, 'data' => $r['data']]);
    }
}
