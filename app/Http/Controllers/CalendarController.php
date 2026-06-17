<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientScope;
use App\Models\Post;
use App\Models\PostLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    // ─────────────────────────────────────────────
    //  MAIN CALENDAR VIEW
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();

        $selectedClientId = (int) $request->get('client_id', $clients->first()?->id);
        $month            = (int) $request->get('month', now()->month);
        $year             = (int) $request->get('year',  now()->year);

        $scopes = ClientScope::where('client_id', $selectedClientId)->get();

        // Load post logs for this client/month — include both:
        //  (a) logs whose planned slot (scheduled_date) is in this month, AND
        //  (b) logs whose linked post was *published* in this month (even if
        //      the original slot was in a different month — so a manually-early
        //      publish shows on today instead of the future planned slot).
        $postLogs = PostLog::with(['posts' => function ($q) {
                $q->select('id', 'post_log_id', 'user_id', 'external_url', 'publish_status', 'final_status', 'best_score', 'scheduled_publish_at', 'published_at');
            }])
            ->where('client_id', $selectedClientId)
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereYear('scheduled_date', $year)
                       ->whereMonth('scheduled_date', $month);
                })->orWhereHas('posts', function ($q2) use ($year, $month) {
                    $q2->whereYear('published_at', $year)
                       ->whereMonth('published_at', $month);
                });
            })
            ->get()
            ->keyBy(fn($log) => $log->client_scope_id . '_' . $log->post_type . '_' . $log->scheduled_date->format('Y-m-d'));

        // Standalone posts: created outside any scope plan but scheduled in this
        // month — show them on the calendar too (Phase 1 #1, additive).
        $standalonePosts = Post::with('client:id,name')
            ->where('client_id', $selectedClientId)
            ->whereNull('post_log_id')
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereYear('scheduled_publish_at', $year)
                       ->whereMonth('scheduled_publish_at', $month);
                })->orWhere(function ($q2) use ($year, $month) {
                    $q2->whereNull('scheduled_publish_at')
                       ->whereYear('scheduled_date', $year)
                       ->whereMonth('scheduled_date', $month);
                });
            })
            ->get();

        $selectedClient = $clients->firstWhere('id', $selectedClientId);

        $calendarData = $this->buildCalendarData($scopes, $year, $month, $postLogs, $selectedClient?->industry);
        $this->appendStandalonePosts($calendarData, $standalonePosts, $selectedClient?->industry);

        return view('clients.calendar', compact(
            'clients',
            'selectedClient',
            'selectedClientId',
            'month',
            'year',
            'calendarData'
        ));
    }

    // ─────────────────────────────────────────────
    //  STATUS UPDATE (AJAX)
    // ─────────────────────────────────────────────
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'client_scope_id' => 'required|exists:client_scopes,id',
            'client_id'       => 'required|exists:clients,id',
            'scope'           => 'required|in:0,1',
            'post_type'       => 'required|string',
            'scheduled_date'  => 'required|date',
            'status'          => 'required|in:pending,completed,missed',
            'note'            => 'nullable|string|max:500',
        ]);

        $log = PostLog::updateOrCreate(
            [
                'client_scope_id' => $validated['client_scope_id'],
                'post_type'       => $validated['post_type'],
                'scheduled_date'  => $validated['scheduled_date'],
            ],
            [
                'client_id' => $validated['client_id'],
                'scope'     => $validated['scope'],
                'status'    => $validated['status'],
                'note'      => $validated['note'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'status'  => $log->status,
            'log_id'  => $log->id,
        ]);
    }

    // ─────────────────────────────────────────────
    //  BUILD CALENDAR DATA
    //
    //  Simple: start_date=12Apr, 10 posts, interval=30/10=3 days
    //  Post dates: 12, 15, 18, 21, 24, 27, 30 Apr, 2, 5, 8 May
    //  Next cycle: 12 May, 15, 18 ... 8 Jun
    //  Show whatever falls in the viewing month.
    // ─────────────────────────────────────────────
    /**
     * #14 — Resolve the recurring weekday content theme for a date.
     */
    private function themeFor(?string $industry, Carbon $date): ?string
    {
        $map = config('content_themes.' . $industry, config('content_themes.default', []));
        return $map[$date->dayOfWeek] ?? null;
    }

    private function buildCalendarData($scopes, int $year, int $month, $postLogs, ?string $industry = null): array
    {
        $postsByDay  = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $viewStart = Carbon::create($year, $month, 1)->startOfDay();
        $viewEnd   = Carbon::create($year, $month, $daysInMonth)->endOfDay();

        foreach ($scopes as $scope) {

            if (!$scope->start_date) continue;

            $startDate = Carbon::parse($scope->start_date)->startOfDay();
            // A superseded plan has an end_date — it stops generating slots after that day.
            $endDate   = $scope->end_date ? Carbon::parse($scope->end_date)->endOfDay() : null;

            // Skip if not started yet, or if this plan version already ended before the view
            if ($viewEnd->lt($startDate)) continue;
            if ($endDate && $viewStart->gt($endDate)) continue;

            $postTypes = $this->getPostTypes($scope);

            foreach ($postTypes as $type => $count) {
                if ($count <= 0) continue;

                // Each billing period is ONE calendar month from the start date
                // (e.g. start 20th → period = 20th to the 19th of next month). Exactly
                // $count posts are spread within each period, so a "6/month" plan always
                // produces 6 in its month — never a 7th at the cycle boundary.

                // Which monthly period (relative to startDate) overlaps the view month.
                $monthsSinceStart = $startDate->diffInMonths($viewStart);

                // Check the period before/at/after to cover overlaps into the view month.
                for ($c = max(0, $monthsSinceStart - 1); $c <= $monthsSinceStart + 1; $c++) {
                    $periodStart = (clone $startDate)->addMonths($c);
                    $periodEnd   = (clone $startDate)->addMonths($c + 1);     // exclusive
                    $periodDays  = $periodStart->diffInDays($periodEnd);      // 28–31
                    $interval    = $periodDays / $count;

                    for ($i = 0; $i < $count; $i++) {
                        $postDate = (clone $periodStart)->addDays((int) round($i * $interval));

                        // Stay within this period (defensive — last post must be < periodEnd)
                        if ($postDate->gte($periodEnd)) continue;

                        // Only show if in viewing month AND within this plan version's window
                        if ($postDate->year !== $year || $postDate->month !== $month) continue;
                        if ($postDate->lt($startDate)) continue;
                        if ($endDate && $postDate->gt($endDate)) continue;

                        $plannedDate   = $postDate->format('Y-m-d');
                        $logKey        = $scope->id . '_' . $type . '_' . $plannedDate;
                        $log           = $postLogs->get($logKey);
                        $bestPost      = $log?->bestPost();

                        // If the post is already published, place it on the
                        // ACTUAL publish day (so manual early publishes show on
                        // today, not on the original planned slot).
                        if ($bestPost && $bestPost->publish_status === 'published' && $bestPost->published_at) {
                            $pubAt = $bestPost->published_at;
                            if ($pubAt->year !== $year || $pubAt->month !== $month) {
                                continue; // published in another month — skip here
                            }
                            $day           = $pubAt->day;
                            $scheduledDate = $pubAt->format('Y-m-d');
                        } else {
                            $day           = $postDate->day;
                            $scheduledDate = $plannedDate;
                        }

                        // No duplicates
                        $exists = isset($postsByDay[$day]) && collect($postsByDay[$day])
                            ->contains(fn($p) => $p['client_scope_id'] === $scope->id
                                && $p['type'] === $type
                                && $p['scheduled_date'] === $scheduledDate);

                        if ($exists) continue;

                        // Effective status: a past-due slot that's still pending counts as MISSED
                        // (the date passed without the post going out). Applies to any client/scope.
                        $rawStatus = $log?->status ?? 'pending';
                        $isPast    = $postDate->lt(Carbon::today());
                        $effStatus = ($rawStatus === 'pending' && $isPast) ? 'missed' : $rawStatus;

                        $postsByDay[$day][] = [
                            'client_scope_id' => $scope->id,
                            'scope'           => (int) $scope->scope,
                            'type'            => $type,
                            'label'           => $this->postTypeLabel($type),
                            'icon'            => $this->postTypeIcon($type),
                            'theme'           => $this->themeFor($industry, $postDate),
                            'scheduled_date'  => $scheduledDate,
                            'status'          => $effStatus,
                            'log_id'          => $log?->id,
                            'note'            => $log?->note,
                            'post_id'         => $bestPost?->id,
                            'external_url'    => $bestPost?->external_url,
                            'publish_status'  => $bestPost?->publish_status,
                            'final_status'    => $bestPost?->final_status,
                            'best_score'      => $bestPost?->best_score,
                            'scheduled_publish_at' => $bestPost?->scheduled_publish_at?->format('Y-m-d\TH:i'),
                        ];
                    }
                }
            }
        }

        // Orphan posts: a slot that has a real post but falls outside the current plan
        // windows (e.g. created under an old plan that was later superseded) must still
        // appear so completed/scheduled work is never hidden.
        foreach ($postLogs as $log) {
            $best = $log->bestPost();
            if (! $best) continue;                       // only logs with an actual post

            // Same rule as the main loop: a published post is anchored on its
            // actual published_at; everything else stays on the planned slot.
            if ($best->publish_status === 'published' && $best->published_at) {
                $pubAt = $best->published_at;
                if ($pubAt->year !== $year || $pubAt->month !== $month) continue;
                $day           = $pubAt->day;
                $scheduledDate = $pubAt->format('Y-m-d');
            } else {
                if ($log->scheduled_date->year !== $year || $log->scheduled_date->month !== $month) continue;
                $day           = $log->scheduled_date->day;
                $scheduledDate = $log->scheduled_date->format('Y-m-d');
            }

            $already = isset($postsByDay[$day]) && collect($postsByDay[$day])
                ->contains(fn($p) => $p['client_scope_id'] === $log->client_scope_id
                    && $p['type'] === $log->post_type
                    && $p['scheduled_date'] === $scheduledDate);
            if ($already) continue;

            $rawStatus = $log->status ?? 'pending';
            $isPast    = $log->scheduled_date->copy()->endOfDay()->lt(Carbon::today());
            $effStatus = ($rawStatus === 'pending' && $isPast) ? 'missed' : $rawStatus;

            $postsByDay[$day][] = [
                'client_scope_id' => $log->client_scope_id,
                'scope'           => (int) $log->scope,
                'type'            => $log->post_type,
                'label'           => $this->postTypeLabel($log->post_type),
                'icon'            => $this->postTypeIcon($log->post_type),
                'theme'           => $this->themeFor($industry, $log->scheduled_date),
                'scheduled_date'  => $scheduledDate,
                'status'          => $effStatus,
                'log_id'          => $log->id,
                'note'            => $log->note,
                'post_id'         => $best->id,
                'external_url'    => $best->external_url,
                'publish_status'  => $best->publish_status,
                'final_status'    => $best->final_status,
                'best_score'      => $best->best_score,
                'scheduled_publish_at' => $best->scheduled_publish_at?->format('Y-m-d\TH:i'),
            ];
        }

        foreach ($postsByDay as $day => $posts) {
            usort($postsByDay[$day], fn($a, $b) => $a['scope'] <=> $b['scope']);
        }

        return $postsByDay;
    }

    // ─────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────
    private function getPostTypes($scope): array
    {
        if ($scope->scope == 0) {
            return [
                'long_video'  => (int) $scope->long_video,
                'short_video' => (int) $scope->short_video,
            ];
        }

        return [
            'story' => (int) $scope->story,
            'photo' => (int) $scope->photo,
            'reels' => (int) $scope->reels,
        ];
    }

    /**
     * Add posts that aren't tied to any scope-plan slot (post_log_id IS NULL)
     * onto the calendar grid by reference to their scheduled date.
     */
    private function appendStandalonePosts(array &$postsByDay, $posts, ?string $industry = null): void
    {
        foreach ($posts as $p) {
            // Pick the date that best represents the post on the calendar:
            // published → actual publish time;
            // scheduled / approved → planned time;
            // otherwise → raw scheduled_date column.
            if ($p->publish_status === 'published' && $p->published_at) {
                $date = $p->published_at;
            } else {
                $date = $p->scheduled_publish_at ?: $p->scheduled_date;
            }
            if (! $date) continue;
            $day  = Carbon::parse($date)->day;

            $postsByDay[$day][] = [
                'client_scope_id' => null,
                'scope'           => (int) $p->scope,
                'type'            => $p->post_type,
                'label'           => $this->postTypeLabel($p->post_type),
                'icon'            => $this->postTypeIcon($p->post_type),
                'theme'           => $this->themeFor($industry, Carbon::parse($date)),
                'scheduled_date'  => Carbon::parse($date)->format('Y-m-d'),
                'status'          => $p->publish_status === 'published' ? 'completed' : 'pending',
                'log_id'          => null,
                'note'            => null,
                'post_id'         => $p->id,
                'external_url'    => $p->external_url,
                'publish_status'  => $p->publish_status,
                'final_status'    => $p->final_status,
                'best_score'      => $p->best_score,
                'scheduled_publish_at' => $p->scheduled_publish_at?->format('Y-m-d\TH:i'),
                'standalone'      => true,
            ];
        }
    }

    private function postTypeLabel(string $type): string
    {
        return match ($type) {
            'long_video'  => 'Long Video',
            'short_video' => 'Short Video',
            'story'       => 'Story',
            'photo'       => 'Photo',
            'reels'       => 'Reels',
            default       => ucfirst($type),
        };
    }

    private function postTypeIcon(string $type): string
    {
        return match ($type) {
            'long_video'  => 'bi-camera-video-fill',
            'short_video' => 'bi-phone-fill',
            'story'       => 'bi-circle-fill',
            'photo'       => 'bi-image-fill',
            'reels'       => 'bi-play-circle-fill',
            default       => 'bi-file',
        };
    }
}
