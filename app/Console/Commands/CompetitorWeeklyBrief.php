<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\CompetitorIntelService;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * AI Studio #2 — Weekly competitor intelligence brief (runs every Monday).
 *
 * For each client with competitor handles configured in config/competitors.php,
 * generates a "what's working for competitors this week" brief and caches it to
 * the filesystem (storage/app/competitor-briefs). Nothing is stored in the DB.
 */
class CompetitorWeeklyBrief extends Command
{
    protected $signature = 'competitors:weekly-brief {--client= : Limit to one client id}';
    protected $description = 'Generate this week\'s competitor intelligence brief for each tracked client';

    public function handle(CompetitorIntelService $intel, GeminiService $gemini): int
    {
        if (! $gemini->isConfigured()) {
            $this->error('GEMINI_API_KEY not set — cannot generate competitor briefs.');
            return self::FAILURE;
        }

        $map = (array) config('competitors.clients', []);
        if (empty($map)) {
            $this->info('No competitors configured in config/competitors.php — nothing to do.');
            return self::SUCCESS;
        }

        $only = $this->option('client') ? (int) $this->option('client') : null;
        $made = 0; $skipped = 0; $failed = 0;

        foreach ($map as $clientId => $handles) {
            $clientId = (int) $clientId;
            if ($only && $clientId !== $only) { continue; }

            $handles = $intel->configuredHandles($clientId);
            if (empty($handles)) { $skipped++; continue; }

            $client = Client::find($clientId);
            if (! $client) {
                $this->warn("  Skipping client #{$clientId} — not found.");
                $skipped++;
                continue;
            }

            $result = $intel->buildBrief($client, $handles);
            if (! ($result['success'] ?? false)) {
                Log::warning('Competitor brief generation failed', ['client' => $clientId, 'err' => $result['error'] ?? 'unknown']);
                $this->error("  ✗ {$client->name} — {$result['error']}");
                $failed++;
                continue;
            }

            $intel->storeBrief($clientId, $handles, $result['data']);
            $made++;
            $this->line("  ✓ {$client->name} — brief generated for " . count($handles) . ' competitor(s).');
        }

        $this->info("Done. Generated {$made}, skipped {$skipped}, failed {$failed}.");
        return self::SUCCESS;
    }
}
