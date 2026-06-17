<?php

namespace App\Services;

use App\Models\ClientScope;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Computes the planned content slots for a client over a date window, using the
 * same monthly-period distribution as the calendar. Also resolves the #14
 * weekday content theme for each slot. Used by the weekly caption batch job and
 * the analytics report.
 */
class ContentPlanService
{
    /**
     * Slots scheduled within [$start, $end] across every active scope plan.
     *
     * @return Collection<int, array{client_id:int, scope:int, post_type:string, date:Carbon, theme:?string}>
     */
    public function upcomingSlots(Carbon $start, Carbon $end, ?int $clientId = null): Collection
    {
        $start = $start->copy()->startOfDay();
        $end   = $end->copy()->endOfDay();

        $scopes = ClientScope::query()
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->whereNotNull('start_date')
            ->with('client:id,industry')
            ->get();

        $slots = collect();

        foreach ($scopes as $scope) {
            $startDate = Carbon::parse($scope->start_date)->startOfDay();
            $endDate   = $scope->end_date ? Carbon::parse($scope->end_date)->endOfDay() : null;
            if ($end->lt($startDate)) continue;
            if ($endDate && $start->gt($endDate)) continue;

            foreach ($this->postTypes($scope) as $type => $count) {
                if ($count <= 0) continue;

                // Walk each monthly period that overlaps the window.
                $cursor = $startDate->copy();
                $guard  = 0;
                while ($cursor->lte($end) && $guard < 240) {
                    $guard++;
                    $periodStart = $cursor->copy();
                    $periodEnd   = $cursor->copy()->addMonth();
                    $periodDays  = $periodStart->diffInDays($periodEnd);
                    $interval    = $periodDays / $count;

                    for ($i = 0; $i < $count; $i++) {
                        $date = $periodStart->copy()->addDays((int) round($i * $interval));
                        if ($date->gte($periodEnd)) continue;
                        if ($date->lt($start) || $date->gt($end)) continue;
                        if ($date->lt($startDate)) continue;
                        if ($endDate && $date->gt($endDate)) continue;

                        $slots->push([
                            'client_id' => (int) $scope->client_id,
                            'scope'     => (int) $scope->scope,
                            'post_type' => $type,
                            'date'      => $date->copy(),
                            'theme'     => $this->themeFor($scope->client->industry ?? null, $date),
                        ]);
                    }

                    $cursor = $periodEnd;
                }
            }
        }

        return $slots->unique(fn ($s) => $s['client_id'] . $s['scope'] . $s['post_type'] . $s['date']->toDateString())
                     ->sortBy(fn ($s) => $s['date']->timestamp)
                     ->values();
    }

    /**
     * #14 — Resolve the content theme for a weekday, honouring industry overrides.
     */
    public function themeFor(?string $industry, CarbonInterface $date): ?string
    {
        $map = config('content_themes.' . $industry, config('content_themes.default', []));
        return $map[$date->dayOfWeek] ?? null;
    }

    private function postTypes(ClientScope $scope): array
    {
        if ((int) $scope->scope === 0) {
            return ['long_video' => (int) $scope->long_video, 'short_video' => (int) $scope->short_video];
        }
        return ['story' => (int) $scope->story, 'photo' => (int) $scope->photo, 'reels' => (int) $scope->reels];
    }
}
