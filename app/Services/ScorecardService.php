<?php

namespace App\Services;

use App\Models\Client;
use Carbon\Carbon;

/**
 * Tool #1 — Content Health Scorecard.
 *
 * One-page monthly health read per client: 6 KPIs, each with a trend arrow
 * (this month vs last month) and a single-line AI comment. Built entirely on
 * the unified ContentInsightsService so it lights up with real reach / saves /
 * shares the moment a client connects Instagram, and still produces a clean
 * card from local quality data when they haven't.
 */
class ScorecardService
{
    public function __construct(
        private ContentInsightsService $insights,
        private GeminiService $gemini,
    ) {}

    /**
     * @return array{client:array, month:string, generated:string, live:array, kpis:array<int,array>, summary:string}
     */
    public function build(Client $client, Carbon $month, bool $fresh = false): array
    {
        $curFrom = $month->copy()->startOfMonth();
        $curTo   = $month->copy()->endOfMonth();
        $prevFrom = $curFrom->copy()->subMonth()->startOfMonth();
        $prevTo   = $curFrom->copy()->subMonth()->endOfMonth();

        // One fetch must reach from NOW back to the start of the PREVIOUS month so
        // both the current and comparison windows are covered — including when an
        // older month is being viewed. Computed via timestamps because Carbon 3's
        // diffInDays is signed/float and silently collapsed this to ~3 days.
        $daysBack = (int) ceil((now()->timestamp - $prevFrom->timestamp) / 86400) + 2;
        $bundle = $this->insights->forClient($client, $daysBack, $fresh);
        $all    = $bundle['items'];

        $cur  = $this->slice($all, $curFrom, $curTo);
        $prev = $this->slice($all, $prevFrom, $prevTo);

        $hasReach  = ContentInsightsService::hasMetric($all, 'reach');
        $hasViews  = ContentInsightsService::hasMetric($all, 'views');
        $hasSaves  = ContentInsightsService::hasMetric($all, 'saves');
        $hasShares = ContentInsightsService::hasMetric($all, 'shares');

        $kpis = [
            $this->kpi('Reach', 'mdi-eye-outline', $this->sum($cur, 'reach'), $this->sum($prev, 'reach'), $hasReach, 'higher'),
            $this->kpi('Views', 'mdi-play-circle-outline', $this->sum($cur, 'views'), $this->sum($prev, 'views'), $hasViews, 'higher'),
            $this->kpi('Saves', 'mdi-bookmark-outline', $this->sum($cur, 'saves'), $this->sum($prev, 'saves'), $hasSaves, 'higher'),
            $this->kpi('Shares', 'mdi-share-variant-outline', $this->sum($cur, 'shares'), $this->sum($prev, 'shares'), $hasShares, 'higher'),
            $this->kpi('Engagement', 'mdi-heart-pulse', $this->sum($cur, 'engagement'), $this->sum($prev, 'engagement'), $this->hasEng($all), 'higher'),
            $this->kpi('Posts published', 'mdi-send-check', count($cur), count($prev), true, 'higher'),
            $this->kpi('Avg quality score', 'mdi-star-outline', $this->avgScore($cur), $this->avgScore($prev), true, 'higher', '/100'),
        ];

        $kpis = $this->addComments($client, $kpis, $month);

        // Per-post breakdown for the month — best performer first (live reach when
        // present, otherwise the AI quality score).
        $rankKey = $hasReach ? 'reach' : 'score';
        usort($cur, fn ($a, $b) => ContentInsightsService::metric($b, $rankKey) <=> ContentInsightsService::metric($a, $rankKey));

        return [
            'client'    => ['id' => $client->id, 'name' => $client->name, 'industry' => $client->industry],
            'month'     => $curFrom->format('F Y'),
            'generated' => now()->format('d M Y, g:i A'),
            'live'      => $bundle['live'],
            'kpis'      => $kpis,
            'summary'   => $this->summaryLine($kpis),
            'posts_this_month' => count($cur),
            'posts'     => $cur,
        ];
    }

    private function slice(array $items, Carbon $from, Carbon $to): array
    {
        return array_values(array_filter($items, fn ($i) => $i['published_at']->between($from, $to)));
    }

    private function sum(array $items, string $field): ?int
    {
        $any = false;
        $total = 0;
        foreach ($items as $i) {
            if (isset($i[$field]) && is_numeric($i[$field])) {
                $any = true;
                $total += (int) $i[$field];
            }
        }
        return $any ? $total : null;
    }

    private function avgScore(array $items): ?float
    {
        $scores = array_filter(array_map(fn ($i) => $i['score'], $items), fn ($s) => $s !== null);
        return $scores ? round(array_sum($scores) / count($scores), 1) : null;
    }

    private function hasEng(array $items): bool
    {
        return ContentInsightsService::hasMetric($items, 'engagement');
    }

    /**
     * Build one KPI cell with trend math.
     */
    private function kpi(string $label, string $icon, $current, $previous, bool $available, string $goodDir, string $suffix = ''): array
    {
        $delta = null;
        $dir   = 'flat';
        if ($available && is_numeric($current) && is_numeric($previous) && $previous > 0) {
            $delta = round(($current - $previous) / $previous * 100, 1);
            $dir = $delta > 1.5 ? 'up' : ($delta < -1.5 ? 'down' : 'flat');
        } elseif ($available && is_numeric($current) && $current > 0 && (!$previous)) {
            $dir = 'up'; // brand-new activity vs nothing
        }

        // Whether the movement is good depends on the metric's desired direction.
        $good = match ($dir) {
            'up'   => $goodDir === 'higher',
            'down' => $goodDir === 'lower',
            default => null,
        };

        return [
            'label'     => $label,
            'icon'      => $icon,
            'available' => $available,
            'current'   => $available ? $current : null,
            'previous'  => $available ? $previous : null,
            'suffix'    => $suffix,
            'delta'     => $delta,
            'direction' => $dir,
            'good'      => $good,
            'comment'   => null, // filled by addComments()
        ];
    }

    /**
     * Ask Gemini for a punchy one-liner per KPI. Falls back to deterministic
     * comments when the AI is unavailable, so the card always renders.
     */
    private function addComments(Client $client, array $kpis, Carbon $month): array
    {
        $fallback = fn (array $k) => $this->ruleComment($k);

        if (! $this->gemini->isConfigured()) {
            foreach ($kpis as $i => $k) {
                $kpis[$i]['comment'] = $fallback($k);
            }
            return $kpis;
        }

        $rows = [];
        foreach ($kpis as $idx => $k) {
            $rows[] = sprintf(
                '%d. %s: %s (prev %s)%s',
                $idx,
                $k['label'],
                $k['available'] ? $this->fmt($k['current']) . $k['suffix'] : 'no data',
                $k['available'] ? $this->fmt($k['previous']) . $k['suffix'] : '—',
                $k['delta'] !== null ? sprintf(', %s%s%%', $k['delta'] >= 0 ? '+' : '', $k['delta']) : ''
            );
        }
        $metricLines = implode("\n", $rows);
        $brand = $client->brandVoiceBlock();
        $kpiCount = count($kpis);
        $exampleJson = implode(', ', array_map(fn ($i) => "\"{$i}\": \"...\"", range(0, $kpiCount - 1)));

        $prompt = \App\Models\Prompt::render('scorecard.comments', [
            'clientName'  => $client->name,
            'industry'    => $client->industry,
            'monthLabel'  => $month->format('F Y'),
            'brand'       => $brand,
            'kpiCount'    => $kpiCount,
            'metricLines' => $metricLines,
            'exampleJson' => $exampleJson,
        ]);

        $res = $this->gemini->generate($prompt, null, '');
        $comments = ($res['success'] ?? false) ? ($res['data']['comments'] ?? []) : [];

        foreach ($kpis as $i => $k) {
            $c = $comments[(string) $i] ?? ($comments[$i] ?? null);
            $kpis[$i]['comment'] = is_string($c) && trim($c) !== '' ? trim($c) : $fallback($k);
        }

        return $kpis;
    }

    private function ruleComment(array $k): string
    {
        if (! $k['available']) {
            return "{$k['label']} not tracked yet — connect Instagram for live figures.";
        }
        $val = $this->fmt($k['current']) . $k['suffix'];
        if ($k['delta'] === null) {
            return "{$k['label']} at {$val} this month.";
        }
        $word = $k['direction'] === 'up' ? 'up' : ($k['direction'] === 'down' ? 'down' : 'flat');
        $abs  = abs($k['delta']);
        return $k['direction'] === 'flat'
            ? "{$k['label']} steady at {$val}."
            : "{$k['label']} {$word} {$abs}% — now {$val}.";
    }

    private function summaryLine(array $kpis): string
    {
        $total = count($kpis);
        $up = $down = 0;
        foreach ($kpis as $k) {
            if ($k['good'] === true) $up++;
            if ($k['good'] === false) $down++;
        }
        if ($up === 0 && $down === 0) return 'Baseline month — trends appear once a second month of data lands.';
        if ($up >= $down) return "Healthy momentum: {$up} of {$total} KPIs improving.";
        return "Needs attention: {$down} KPIs slipping — review the comments below.";
    }

    private function fmt($n): string
    {
        if ($n === null) return '—';
        if (is_float($n)) return rtrim(rtrim(number_format($n, 1), '0'), '.');
        return number_format((int) $n);
    }
}