<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        $clients  = Client::where('status', 'active')->orderBy('name')->get();
        $clientId = (int) $request->get('client_id', $clients->first()?->id);
        $monthRaw = $request->get('month', now()->format('Y-m'));

        $client = $clients->firstWhere('id', $clientId);
        $month  = Carbon::createFromFormat('Y-m', $monthRaw)->startOfMonth();

        $report = $client ? $this->analytics->report($client, $month) : null;

        return view('reports.analytics', [
            'clients'  => $clients,
            'clientId' => $clientId,
            'month'    => $monthRaw,
            'report'   => $report,
        ]);
    }

    /** JSON variant (for async refresh / future export). */
    public function data(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'month'     => 'nullable|date_format:Y-m',
        ]);

        $client = Client::findOrFail($request->get('client_id'));
        $month  = Carbon::createFromFormat('Y-m', $request->get('month', now()->format('Y-m')))->startOfMonth();

        return response()->json(['success' => true, 'report' => $this->analytics->report($client, $month)]);
    }
}
