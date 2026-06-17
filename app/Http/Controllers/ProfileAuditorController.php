<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\GeminiService;
use Illuminate\Http\Request;

/**
 * AI Studio #5 — Competitor Profile Auditor.
 *
 * Drop a competitor Instagram handle → AI audits the kind of content that wins
 * on that profile (format, posting frequency, caption style, hashtag strategy)
 * and produces a "steal their playbook" brief tailored to your client.
 * Nothing is persisted to the database.
 */
class ProfileAuditorController extends Controller
{
    public function index()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);

        return view('tools.profile-auditor', compact('clients'));
    }

    public function audit(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'handle'    => 'required|string|max:120',
            'niche'     => 'nullable|string|max:160',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.']);
        }

        $handle = ltrim(trim($data['handle']), '@');
        if ($handle === '') {
            return response()->json(['success' => false, 'error' => 'Enter a valid Instagram handle.']);
        }

        $client     = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $clientName = $client?->name ?? 'your client';
        $niche      = trim((string) ($data['niche'] ?? $client?->industry ?? ''));
        $nicheLine  = $niche !== '' ? "Niche / industry context: \"{$niche}\"." : '';

        $prompt = \App\Models\Prompt::render('profile.auditor', [
            'brandBlock' => $brandBlock,
            'handle'     => $handle,
            'nicheLine'  => $nicheLine,
            'clientName' => $clientName,
        ]);

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            return response()->json(['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown error')]);
        }

        return response()->json(['success' => true, 'data' => $r['data']]);
    }
}
