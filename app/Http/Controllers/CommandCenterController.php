<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContentInsightsService;
use App\Services\GeminiService;
use Illuminate\Http\Request;

/**
 * Tool #2 — All-Platform Content Command Center.
 *
 * Every post / reel / short across every connected platform in ONE ranked table.
 * Sort by the metric that matters (reach, saves, shares, plays, engagement or AI
 * score), colour-coded by format, with a "This format wins for you" badge on the
 * format that consistently out-performs on the chosen metric.
 */
class CommandCenterController extends Controller
{
    /** Metrics offered in the sort dropdown → human label. */
    private const METRICS = [
        'reach'      => 'Reach',
        'saves'      => 'Saves',
        'shares'     => 'Shares',
        'plays'      => 'Plays / Views',
        'engagement' => 'Engagement',
        'score'      => 'AI Quality Score',
    ];

    public function __construct(
        private ContentInsightsService $insights,
        private GeminiService $gemini,
    ) {}

    public function index(Request $request)
    {
        $clients  = Client::where('status', 'active')->orderBy('name')->get();
        $clientId = (int) $request->get('client_id', $clients->first()?->id);
        $days     = (int) $request->get('days', 90);
        $client   = $clients->firstWhere('id', $clientId);

        $data = null;
        if ($client) {
            $bundle = $this->insights->forClient($client, in_array($days, [30, 60, 90, 180], true) ? $days : 90, $request->boolean('fresh'));
            $items  = $bundle['items'];

            // Default sort: the best available live metric, else AI score.
            $sort = $request->get('sort');
            if (! array_key_exists($sort, self::METRICS)) {
                $sort = ContentInsightsService::hasMetric($items, 'reach') ? 'reach' : 'score';
            }

            usort($items, fn ($a, $b) => ContentInsightsService::metric($b, $sort) <=> ContentInsightsService::metric($a, $sort));

            $winningFormat = $this->winningFormat($items, $sort);

            $data = [
                'items'          => $items,
                'sort'           => $sort,
                'metrics'        => self::METRICS,
                'live'           => $bundle['live'],
                'winning_format' => $winningFormat,
                'format_meta'    => ContentInsightsService::FORMATS,
                'insight'        => $this->aiInsight($client, $items, $sort, $winningFormat),
                'totals'         => $this->totals($items),
            ];
        }

        return view('tools.command-center', [
            'clients'  => $clients,
            'clientId' => $clientId,
            'days'     => $days,
            'data'     => $data,
        ]);
    }

    /**
     * The format with the best AVERAGE on the chosen metric (min 2 posts so a
     * single lucky post doesn't crown a format). Returns null when there isn't
     * enough data to call it.
     */
    private function winningFormat(array $items, string $metric): ?string
    {
        $byFormat = [];
        foreach ($items as $it) {
            $byFormat[$it['format']][] = ContentInsightsService::metric($it, $metric);
        }

        $best = null; $bestAvg = -1;
        foreach ($byFormat as $fmt => $vals) {
            if (count($vals) < 2) continue;
            $avg = array_sum($vals) / count($vals);
            if ($avg > $bestAvg) { $bestAvg = $avg; $best = $fmt; }
        }
        return $best;
    }

    private function totals(array $items): array
    {
        $t = ['posts' => count($items)];
        foreach (['reach', 'saves', 'shares', 'plays', 'engagement'] as $m) {
            $sum = 0; $any = false;
            foreach ($items as $i) {
                if (isset($i[$m]) && is_numeric($i[$m])) { $sum += $i[$m]; $any = true; }
            }
            $t[$m] = $any ? $sum : null;
        }
        return $t;
    }

    private function aiInsight(Client $client, array $items, string $sort, ?string $winningFormat): ?string
    {
        if (! $this->gemini->isConfigured() || empty($items)) {
            return null;
        }

        $top = array_slice($items, 0, 8);
        $lines = [];
        foreach ($top as $i => $it) {
            $lines[] = sprintf('%d. [%s/%s] "%s" — reach %s, saves %s, shares %s, score %s',
                $i + 1, $it['platform'], $it['format_label'],
                mb_substr($it['title'], 0, 40),
                $this->n($it['reach']), $this->n($it['saves']), $this->n($it['shares']), $this->n($it['score']));
        }
        $list = implode("\n", $lines);
        $brand = $client->brandVoiceBlock();

        $prompt = \App\Models\Prompt::render('command.insight', [
            'clientName' => $client->name,
            'sort'       => $sort,
            'brand'      => $brand,
            'list'       => $list,
        ]);

        $res = $this->gemini->generate($prompt, null, '');
        $txt = ($res['success'] ?? false) ? trim((string) ($res['data']['insight'] ?? '')) : '';
        return $txt !== '' ? $txt : null;
    }

    private function n($v): string
    {
        return is_numeric($v) ? number_format((int) $v) : '—';
    }
}
