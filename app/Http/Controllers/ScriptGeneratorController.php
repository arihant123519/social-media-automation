<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\GeminiService;
use Illuminate\Http\Request;

/**
 * AI Studio #1 — AI Video Script Generator.
 *
 * Doctor inputs topic + specialty → Gemini writes a 30/60-sec Reel script with
 * a hook, body and CTA in the client's own brand voice. Nothing is persisted —
 * the script is generated on demand and returned to the browser only.
 */
class ScriptGeneratorController extends Controller
{
    public function index()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);

        return view('tools.script', compact('clients'));
    }

    public function generate(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'topic'     => 'required|string|max:300',
            'specialty' => 'required|string|max:160',
            'duration'  => 'required|in:30,60',
            'platform'  => 'nullable|in:Instagram,YouTube',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.']);
        }

        $client     = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $platform   = $data['platform'] ?? 'Instagram';
        $seconds    = (int) $data['duration'];

        // Rough beat budget so the script actually fits the runtime.
        $beats = $seconds === 30 ? '3-4' : '5-7';
        $words = $seconds === 30 ? '70-90' : '140-170';

        $prompt = \App\Models\Prompt::render('script.generate', [
            'brandBlock' => $brandBlock,
            'seconds'    => $seconds,
            'platform'   => $platform,
            'topic'      => $data['topic'],
            'specialty'  => $data['specialty'],
            'words'      => $words,
            'beats'      => $beats,
        ]);

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            return response()->json(['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown error')]);
        }

        return response()->json(['success' => true, 'data' => $r['data']]);
    }
}
