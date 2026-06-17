<?php

namespace App\Services;

use App\Models\Client;

/**
 * Tool #4 — Viral Probability Predictor.
 *
 * Before publishing, paste the planned caption, hook, format and topic. We pull
 * the client's actual historical top performers (their "viral DNA") and ask
 * Gemini to score how closely the draft matches it, predict a reach range, and
 * give a go / improve / no-go call with concrete tweaks. The reach range is
 * grounded in the client's real reach distribution so it isn't a fantasy number.
 */
class ViralPredictorService
{
    public function __construct(
        private ContentInsightsService $insights,
        private GeminiService $gemini,
    ) {}

    /**
     * @param array{caption?:string,hook?:string,format?:string,topic?:string,script?:string} $draft
     */
    public function predict(Client $client, array $draft): array
    {
        if (! $this->gemini->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not set — configure it in Settings first.'];
        }

        $items = $this->insights->items($client, 180);

        // Rank historical content by reach (live) else AI score → the "viral DNA".
        $useReach = ContentInsightsService::hasMetric($items, 'reach');
        $rankKey  = $useReach ? 'reach' : 'score';
        usort($items, fn ($a, $b) => ContentInsightsService::metric($b, $rankKey) <=> ContentInsightsService::metric($a, $rankKey));

        $top = array_slice($items, 0, 8);

        // Real reach distribution to ground the prediction.
        $reaches = array_values(array_filter(array_map(fn ($i) => $i['reach'], $items), 'is_numeric'));
        $reachCtx = '';
        if (! empty($reaches)) {
            sort($reaches);
            $median = $reaches[intdiv(count($reaches), 2)];
            $reachCtx = sprintf(
                "Historical reach for this client — min %s, median %s, max %s (across %d measured posts). Keep the predicted range realistic against these.",
                number_format(min($reaches)), number_format($median), number_format(max($reaches)), count($reaches)
            );
        } else {
            $reachCtx = "No live reach data yet for this client — base the predicted reach range on the AI quality scores below and typical small-business social reach. Clearly label it an estimate.";
        }

        $dnaLines = [];
        foreach ($top as $i => $it) {
            $dnaLines[] = sprintf(
                "%d. [%s/%s] topic-hook: \"%s\" | reach: %s | saves: %s | shares: %s | AI score: %s",
                $i + 1, $it['platform'], $it['format_label'],
                mb_substr(trim($it['title'] . ' — ' . $it['caption']), 0, 90),
                is_numeric($it['reach']) ? number_format($it['reach']) : 'n/a',
                is_numeric($it['saves']) ? number_format($it['saves']) : 'n/a',
                is_numeric($it['shares']) ? number_format($it['shares']) : 'n/a',
                $it['score'] ?? 'n/a'
            );
        }
        $dna = empty($dnaLines)
            ? '(No published history yet — judge the draft on general best-practice for this niche and the brand voice.)'
            : implode("\n", $dnaLines);

        $brand    = $client->brandVoiceBlock();
        $caption  = trim((string) ($draft['caption'] ?? ''));
        $hook     = trim((string) ($draft['hook'] ?? ''));
        $format   = trim((string) ($draft['format'] ?? ''));
        $topic    = trim((string) ($draft['topic'] ?? ''));
        $script   = trim((string) ($draft['script'] ?? ''));
        $fmtLabel = ContentInsightsService::FORMATS[$format]['label'] ?? ($format ?: 'not specified');

        $scriptBlock = $script !== ''
            ? "\n- Full script / content (judge this as the primary draft):\n\"\"\"\n{$script}\n\"\"\""
            : '';

        $prompt = \App\Models\Prompt::render('viral.predictor', [
            'clientName'  => $client->name,
            'industry'    => $client->industry,
            'brand'       => $brand,
            'dna'         => $dna,
            'reachCtx'    => $reachCtx,
            'fmtLabel'    => $fmtLabel,
            'topic'       => $topic,
            'hook'        => $hook,
            'caption'     => $caption,
            'scriptBlock' => $scriptBlock,
        ]);

        $res = $this->gemini->generate($prompt, null, '');
        if (! ($res['success'] ?? false) || ! is_array($res['data'] ?? null)) {
            return ['success' => false, 'error' => 'Gemini: ' . ($res['error'] ?? 'unknown error')];
        }

        $data = $res['data'];
        $data['has_history'] = ! empty($dnaLines);
        $data['live_reach']  = $useReach;

        return ['success' => true, 'data' => $data];
    }
}
