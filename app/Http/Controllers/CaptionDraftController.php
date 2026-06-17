<?php

namespace App\Http\Controllers;

use App\Models\CaptionDraft;
use App\Models\Client;
use App\Services\ContentPlanService;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CaptionDraftController extends Controller
{
    /**
     * #12 — Review next week's AI caption drafts.
     */
    public function index(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $clientId = (int) $request->get('client_id', $clients->first()?->id);

        $drafts = CaptionDraft::with('client:id,name')
            ->where('client_id', $clientId)
            ->where('status', '!=', 'discarded')
            ->orderBy('scheduled_date')
            ->get()
            ->groupBy(fn ($d) => $d->scheduled_date->format('Y-m-d'));

        $selectedClient = $clients->firstWhere('id', $clientId);

        return view('captions.index', compact('clients', 'selectedClient', 'clientId', 'drafts'));
    }

    /**
     * Save an SMO Exec's edits to a draft.
     */
    public function update(Request $request, CaptionDraft $draft)
    {
        $data = $request->validate([
            'caption'  => 'nullable|string|max:2500',
            'hashtags' => 'nullable|string|max:600',
        ]);

        $draft->update([
            'caption'  => $data['caption'] ?? $draft->caption,
            'hashtags' => $data['hashtags'] ?? $draft->hashtags,
            'status'   => 'edited',
        ]);

        return response()->json(['success' => true, 'status' => $draft->status]);
    }

    public function destroy(CaptionDraft $draft)
    {
        $draft->update(['status' => 'discarded']);
        return response()->json(['success' => true]);
    }

    /**
     * Manually trigger generation for the next week (for the current client or all).
     */
    public function generate(Request $request, ContentPlanService $planner, GeminiService $gemini)
    {
        $request->validate(['client_id' => 'nullable|exists:clients,id']);

        if (! $gemini->isConfigured()) {
            return back()->with('error', 'Gemini API key not set — configure it in Settings first.');
        }

        $clientId = $request->input('client_id');
        $exit = \Illuminate\Support\Facades\Artisan::call('captions:generate-weekly', array_filter([
            '--client' => $clientId,
        ]));

        $url = route('captions.index', array_filter(['client_id' => $clientId]));
        return redirect($url)->with($exit === 0 ? 'success' : 'error',
            $exit === 0 ? 'Caption drafts generated for the next 7 days.' : 'Generation finished with errors — check logs.');
    }
}
