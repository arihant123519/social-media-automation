<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\BestTimeService;
use Illuminate\Http\Request;

/**
 * Tool #3 — Best Time To Post Calculator.
 */
class BestTimeController extends Controller
{
    public function __construct(private BestTimeService $bestTime) {}

    public function index(Request $request)
    {
        $clients  = Client::where('status', 'active')->orderBy('name')->get();
        $clientId = (int) $request->get('client_id', $clients->first()?->id);
        $days     = (int) $request->get('days', 90);
        $days     = in_array($days, [30, 60, 90, 180], true) ? $days : 90;
        $platform = $request->get('platform', 'all');
        $format   = $request->get('format', 'all');

        $client = $clients->firstWhere('id', $clientId);
        $data   = $client ? $this->bestTime->build($client, $days, $platform, $format, $request->boolean('fresh')) : null;

        return view('tools.best-time', [
            'clients'  => $clients,
            'clientId' => $clientId,
            'days'     => $days,
            'data'     => $data,
        ]);
    }
}
