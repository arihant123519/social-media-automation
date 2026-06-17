<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ScorecardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Tool #1 — Content Health Scorecard (per client, per month).
 */
class ScorecardController extends Controller
{
    public function __construct(private ScorecardService $scorecards) {}

    public function index(Request $request)
    {
        $clients  = Client::where('status', 'active')->orderBy('name')->get();
        $clientId = (int) $request->get('client_id', $clients->first()?->id);
        $monthRaw = $request->get('month', now()->format('Y-m'));

        $client = $clients->firstWhere('id', $clientId);
        $month  = Carbon::createFromFormat('Y-m', $monthRaw)->startOfMonth();

        $card = $client ? $this->scorecards->build($client, $month, $request->boolean('fresh')) : null;

        return view('tools.scorecard', [
            'clients'  => $clients,
            'clientId' => $clientId,
            'month'    => $monthRaw,
            'card'     => $card,
        ]);
    }
}
