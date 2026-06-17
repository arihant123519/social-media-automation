<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContentInsightsService;
use App\Services\ViralPredictorService;
use Illuminate\Http\Request;

/**
 * Tool #4 — Viral Probability Predictor.
 */
class ViralPredictorController extends Controller
{
    public function __construct(private ViralPredictorService $predictor) {}

    public function index()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry']);

        return view('tools.viral-predictor', [
            'clients' => $clients,
            'formats' => ContentInsightsService::FORMATS,
        ]);
    }

    public function predict(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'topic'     => 'required|string|max:300',
            'hook'      => 'required|string|max:500',
            'caption'   => 'nullable|string|max:3000',
            'script'    => 'nullable|string|max:20000',
            'format'    => 'required|string|max:30',
        ]);

        $client = Client::findOrFail($data['client_id']);

        $result = $this->predictor->predict($client, [
            'caption' => $data['caption'] ?? '',
            'hook'    => $data['hook'],
            'format'  => $data['format'],
            'topic'   => $data['topic'],
            'script'  => $data['script'] ?? '',
        ]);

        return response()->json($result);
    }
}
