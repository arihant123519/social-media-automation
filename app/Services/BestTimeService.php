<?php

namespace App\Services;

use App\Models\Client;

/**
 * Tool #3 — Best Time To Post Calculator.
 *
 * Feeds the client's last ~60+ days of post timestamps + reach (or AI score when
 * reach isn't live) into a day-of-week × hour heatmap. Each cell is the AVERAGE
 * performance of posts published in that slot, so it surfaces the slots that
 * consistently out-perform for THIS client's audience — not generic "post at 6pm"
 * advice. Filterable per platform and per content type.
 */
class BestTimeService
{
    private const DAYS  = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    public function __construct(private ContentInsightsService $insights) {}

    /**
     * @return array
     */
    public function build(Client $client, int $days = 90, ?string $platform = null, ?string $format = null, bool $fresh = false): array
    {
        $bundle = $this->insights->forClient($client, $days, $fresh);
        $items  = $bundle['items'];

        // Filters
        $filtered = array_values(array_filter($items, function ($it) use ($platform, $format) {
            if ($platform && $platform !== 'all' && $it['platform'] !== $platform) return false;
            if ($format && $format !== 'all' && $it['format'] !== $format) return false;
            return true;
        }));

        $useReach = ContentInsightsService::hasMetric($filtered, 'reach');
        $weightKey = $useReach ? 'reach' : 'score';

        // 7 days × 24 hours grid of {sum, count}
        $grid = [];
        for ($d = 0; $d < 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $grid[$d][$h] = ['sum' => 0.0, 'count' => 0];
            }
        }

        foreach ($filtered as $it) {
            $dt = $it['published_at'];
            $dow = ((int) $dt->dayOfWeekIso) - 1; // 0=Mon .. 6=Sun
            $hour = (int) $dt->format('G');
            $w = ContentInsightsService::metric($it, $weightKey);
            $grid[$dow][$hour]['sum'] += $w;
            $grid[$dow][$hour]['count']++;
        }

        // Average per cell + global max for colour scaling.
        $cells = [];
        $maxAvg = 0.0;
        for ($d = 0; $d < 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $c = $grid[$d][$h];
                $avg = $c['count'] ? $c['sum'] / $c['count'] : 0.0;
                $cells[$d][$h] = ['avg' => $avg, 'count' => $c['count']];
                if ($avg > $maxAvg) $maxAvg = $avg;
            }
        }

        // Recommended slots: top cells with at least 1 post, ranked by avg then count.
        $slots = [];
        for ($d = 0; $d < 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                if ($cells[$d][$h]['count'] > 0) {
                    $slots[] = [
                        'day'   => self::DAYS[$d],
                        'dow'   => $d,
                        'hour'  => $h,
                        'label' => self::DAYS[$d] . ' ' . $this->hourLabel($h),
                        'avg'   => round($cells[$d][$h]['avg'], 1),
                        'count' => $cells[$d][$h]['count'],
                    ];
                }
            }
        }
        usort($slots, fn ($a, $b) => [$b['avg'], $b['count']] <=> [$a['avg'], $a['count']]);
        $topSlots = array_slice($slots, 0, 5);

        // Best day + best hour rollups (handy single-line takeaways).
        $bestDay  = $this->bestBucket($cells, 'day');
        $bestHour = $this->bestBucket($cells, 'hour');

        return [
            'client'      => ['id' => $client->id, 'name' => $client->name],
            'days_label'  => self::DAYS,
            'cells'       => $cells,
            'max_avg'     => $maxAvg,
            'weight'      => $useReach ? 'reach' : 'AI quality score',
            'live'        => $useReach,
            'total_posts' => count($filtered),
            'top_slots'   => $topSlots,
            'best_day'    => $bestDay,
            'best_hour'   => $bestHour,
            'platforms'   => $this->distinct($items, 'platform'),
            'formats'     => $this->distinctFormats($items),
            'platform'    => $platform ?: 'all',
            'format'      => $format ?: 'all',
        ];
    }

    private function bestBucket(array $cells, string $mode): ?array
    {
        $agg = [];
        for ($d = 0; $d < 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $c = $cells[$d][$h];
                if ($c['count'] === 0) continue;
                $k = $mode === 'day' ? $d : $h;
                $agg[$k]['sum'] = ($agg[$k]['sum'] ?? 0) + $c['avg'] * $c['count'];
                $agg[$k]['count'] = ($agg[$k]['count'] ?? 0) + $c['count'];
            }
        }
        if (empty($agg)) return null;

        $best = null; $bestAvg = -1;
        foreach ($agg as $k => $v) {
            $avg = $v['sum'] / max(1, $v['count']);
            if ($avg > $bestAvg) { $bestAvg = $avg; $best = $k; }
        }
        return [
            'label' => $mode === 'day' ? self::DAYS[$best] : $this->hourLabel($best),
            'avg'   => round($bestAvg, 1),
        ];
    }

    private function distinct(array $items, string $field): array
    {
        $vals = array_values(array_unique(array_map(fn ($i) => $i[$field], $items)));
        sort($vals);
        return $vals;
    }

    private function distinctFormats(array $items): array
    {
        $out = [];
        foreach ($items as $i) {
            $out[$i['format']] = $i['format_label'];
        }
        return $out;
    }

    private function hourLabel(int $h): string
    {
        $ampm = $h < 12 ? 'AM' : 'PM';
        $hr = $h % 12; if ($hr === 0) $hr = 12;
        return $hr . ' ' . $ampm;
    }
}
