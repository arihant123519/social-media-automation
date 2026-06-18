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
            'duration'  => 'required|integer|min:15|max:1200',
            'platform'  => 'nullable|in:Instagram,YouTube',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.']);
        }

        $client     = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $platform   = $data['platform'] ?? 'Instagram';
        $seconds    = (int) $data['duration'];

        // Rough budgets so the script actually fits the runtime. ~135 spoken words/min,
        // and roughly one body beat per ~12s, capped so long videos read as chapters.
        $wordTarget = (int) round($seconds / 60 * 135);
        $words      = max(40, (int) round($wordTarget * 0.85)) . '-' . (int) round($wordTarget * 1.1);

        $beatTarget = min(24, max(3, (int) round($seconds / 12)));
        $beats      = max(3, $beatTarget - 1) . '-' . ($beatTarget + 2);

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
