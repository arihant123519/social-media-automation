<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\CompetitorIntelService;
use Illuminate\Http\Request;

/**
 * AI Studio #2 — Competitor Post Intelligence Feed.
 *
 * Tracks 3-5 competitor Instagram pages per client. Each Monday a scheduled
 * command refreshes a "what's working for competitors this week" brief; the user
 * can also run one on demand here. Briefs are cached on the filesystem only —
 * nothing is written to the database.
 */
class CompetitorFeedController extends Controller
{
    public function __construct(private CompetitorIntelService $intel) {}

    public function index(Request $request)
    {
        $clients  = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);
        $clientId = (int) $request->get('client_id', $clients->first()?->id);

        $configured = $clientId ? $this->intel->configuredHandles($clientId) : [];
        $latest     = $clientId ? $this->intel->latestBrief($clientId) : null;

        return view('tools.competitors', compact('clients', 'clientId', 'configured', 'latest'));
    }

    public function analyze(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'handles'   => 'required|array|min:1|max:5',
            'handles.*' => 'nullable|string|max:120',
        ]);

        $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        $result = $this->intel->buildBrief($client, $data['handles']);

        if (! ($result['success'] ?? false)) {
            return response()->json(['success' => false, 'error' => $result['error'] ?? 'unknown error']);
        }

        // Cache to filesystem (not DB) so the brief survives a page reload.
        if ($client) {
            $handles = collect($data['handles'])->map(fn ($h) => ltrim(trim((string) $h), '@'))->filter()->values()->all();
            $this->intel->storeBrief($client->id, $handles, $result['data']);
        }

        return response()->json(['success' => true, 'data' => $result['data'], 'generated_at' => now()->toIso8601String()]);
    }
}
