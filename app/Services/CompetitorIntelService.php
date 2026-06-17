<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Storage;

/**
 * AI Studio #2 — Competitor Post Intelligence.
 *
 * Generates a weekly "what's working for competitors this week" brief from a set
 * of competitor Instagram handles, using Gemini. Briefs are cached to the local
 * filesystem (storage/app/competitor-briefs) — NEVER the database — so the UI
 * and the Monday scheduler can share the same result.
 */
class CompetitorIntelService
{
    private const DIR = 'competitor-briefs';

    public function __construct(private GeminiService $gemini) {}

    /**
     * Build a fresh brief for one client from a list of competitor handles.
     *
     * @param  string[]  $handles
     * @return array{success: bool, data?: array, error?: string}
     */
    public function buildBrief(?Client $client, array $handles): array
    {
        if (! $this->gemini->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not set.'];
        }

        $handles = collect($handles)
            ->map(fn ($h) => ltrim(trim((string) $h), '@'))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        if (empty($handles)) {
            return ['success' => false, 'error' => 'Add at least one competitor handle.'];
        }

        $brandBlock = $client?->brandVoiceBlock() ?? '';
        $clientName = $client?->name ?? 'your client';
        $niche      = trim((string) ($client?->industry ?? ''));
        $nicheLine  = $niche !== '' ? "Client niche / industry: \"{$niche}\"." : '';
        $handleList = implode(', ', array_map(fn ($h) => '@' . $h, $handles));

        $prompt = \App\Models\Prompt::render('competitor.brief', [
            'brandBlock' => $brandBlock,
            'clientName' => $clientName,
            'nicheLine'  => $nicheLine,
            'handleList' => $handleList,
        ]);

        $r = $this->gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            return ['success' => false, 'error' => 'Gemini: ' . ($r['error'] ?? 'unknown error')];
        }

        return ['success' => true, 'data' => $r['data']];
    }

    /**
     * Persist a brief to the local filesystem (NOT the database).
     */
    public function storeBrief(int $clientId, array $handles, array $brief): void
    {
        $payload = [
            'client_id'    => $clientId,
            'handles'      => array_values($handles),
            'generated_at' => now()->toIso8601String(),
            'week'         => now()->format('o-\WW'),
            'brief'        => $brief,
        ];
        Storage::disk('local')->put(
            self::DIR . "/client-{$clientId}.json",
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Load the latest stored brief for a client, or null when none exists.
     */
    public function latestBrief(int $clientId): ?array
    {
        $path = self::DIR . "/client-{$clientId}.json";
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }
        $decoded = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Configured competitor handles for a client (from config/competitors.php, file-based — not DB).
     *
     * @return string[]
     */
    public function configuredHandles(int $clientId): array
    {
        $map = (array) config('competitors.clients', []);
        $handles = $map[$clientId] ?? [];

        return collect($handles)->map(fn ($h) => ltrim(trim((string) $h), '@'))->filter()->values()->all();
    }
}
