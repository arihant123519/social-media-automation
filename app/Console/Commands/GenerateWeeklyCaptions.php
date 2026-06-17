<?php

namespace App\Console\Commands;

use App\Models\CaptionDraft;
use App\Models\Client;
use App\Services\ContentPlanService;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * #12 — Batch-generate next week's caption drafts (runs every Sunday).
 * For each planned slot in the coming 7 days it produces an on-brand caption +
 * hashtag set with Gemini, which the SMO Exec then reviews/edits at /caption-drafts.
 */
class GenerateWeeklyCaptions extends Command
{
    protected $signature = 'captions:generate-weekly {--days=7 : How many days ahead to plan} {--client= : Limit to one client id}';
    protected $description = 'Generate AI caption drafts for next week\'s planned slots';

    public function handle(ContentPlanService $planner, GeminiService $gemini): int
    {
        if (! $gemini->isConfigured()) {
            $this->error('GEMINI_API_KEY not set — cannot generate captions.');
            return self::FAILURE;
        }

        $days  = max(1, (int) $this->option('days'));
        $start = Carbon::tomorrow();
        $end   = Carbon::today()->addDays($days);

        $slots = $planner->upcomingSlots($start, $end, $this->option('client') ? (int) $this->option('client') : null);

        if ($slots->isEmpty()) {
            $this->info('No planned slots in the window — nothing to generate.');
            return self::SUCCESS;
        }

        $clients = Client::whereIn('id', $slots->pluck('client_id')->unique())->get()->keyBy('id');
        $made = 0; $skipped = 0; $failed = 0;

        $this->info("Planning {$slots->count()} slot(s) from {$start->toDateString()} to {$end->toDateString()}…");

        foreach ($slots as $slot) {
            $exists = CaptionDraft::where('client_id', $slot['client_id'])
                ->where('scope', $slot['scope'])
                ->where('post_type', $slot['post_type'])
                ->whereDate('scheduled_date', $slot['date'])
                ->exists();
            if ($exists) { $skipped++; continue; }

            $client = $clients->get($slot['client_id']);
            if (! $client) { $skipped++; continue; }

            $result = $this->generateOne($gemini, $client, $slot);
            if (! $result) { $failed++; continue; }

            CaptionDraft::create([
                'client_id'      => $slot['client_id'],
                'scope'          => $slot['scope'],
                'post_type'      => $slot['post_type'],
                'scheduled_date' => $slot['date']->toDateString(),
                'theme'          => $slot['theme'],
                'keyword'        => $slot['theme'],
                'caption'        => $result['caption'] ?? null,
                'hashtags'       => $result['hashtags'] ?? null,
                'status'         => 'draft',
                'generated_at'   => now(),
            ]);
            $made++;
            $this->line("  ✓ {$client->name} — {$slot['post_type']} on {$slot['date']->toDateString()} ({$slot['theme']})");
        }

        $this->info("Done. Created {$made}, skipped {$skipped}, failed {$failed}.");
        return self::SUCCESS;
    }

    private function generateOne(GeminiService $gemini, Client $client, array $slot): ?array
    {
        $brand    = $client->brandVoiceBlock();
        $platform = match ((int) $slot['scope']) {
            0 => 'YouTube', 1 => 'Instagram', 2 => 'Facebook', 3 => 'LinkedIn', default => 'social media',
        };
        $theme = $slot['theme'] ?: 'general';
        $date  = $slot['date']->format('l, j M Y');

        $prompt = \App\Models\Prompt::render('captions.weekly', [
            'brand'     => $brand,
            'platform'  => $platform,
            'theme'     => $theme,
            'date'      => $date,
            'industry'  => $client->industry,
            'post_type' => $slot['post_type'],
        ]);

        $r = $gemini->generate($prompt, null, '');
        if (! ($r['success'] ?? false) || ! is_array($r['data'] ?? null)) {
            Log::warning('Weekly caption generation failed', ['client' => $client->id, 'err' => $r['error'] ?? 'unknown']);
            return null;
        }
        return $r['data'];
    }
}
