<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Requires ONE OS cron entry on the server:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
| (Windows: a Task Scheduler task running `php artisan schedule:run` every minute,
|  or keep `php artisan schedule:work` running.)
*/

// #11 — Auto-publish posts whose scheduled time has arrived (every minute).
Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// #11 — Email a "publishing tomorrow" reminder once a day (08:00 app time).
Schedule::command('posts:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// #12 — Batch-generate next week's caption drafts every Sunday (18:00 app time).
Schedule::command('captions:generate-weekly')
    ->weeklyOn(0, '18:00')
    ->withoutOverlapping();

// #11 — Keep Meta (IG + FB) long-lived tokens alive (weekly, well inside the 60-day window).
Schedule::command('meta:refresh-tokens')
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping();

// AI Studio #2 — Generate competitor intelligence briefs every Monday (09:00 app time).
Schedule::command('competitors:weekly-brief')
    ->weeklyOn(1, '09:00')
    ->withoutOverlapping();
