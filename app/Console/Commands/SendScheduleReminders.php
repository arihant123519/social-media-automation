<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Notifications\PostScheduleReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Emails post owners one day before a scheduled post auto-publishes.
 * Runs once daily via the scheduler (routes/console.php).
 */
class SendScheduleReminders extends Command
{
    protected $signature = 'posts:send-reminders';
    protected $description = 'Email a reminder for posts scheduled to auto-publish tomorrow';

    public function handle(): int
    {
        // Posts scheduled to publish TOMORROW (the "one day before" reminder).
        // Same-day scheduled posts get no reminder — there's no day-before left.
        $posts = Post::with(['user', 'client'])
            ->where('publish_status', 'scheduled')
            ->whereNull('reminder_sent_at')
            ->whereNotNull('scheduled_publish_at')
            ->whereDate('scheduled_publish_at', \Carbon\Carbon::tomorrow())
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No reminders to send.');
            return self::SUCCESS;
        }

        $this->info("Sending {$posts->count()} reminder(s)...");

        $to = config('services.notify.email');
        if (! $to) {
            $this->error('NOTIFY_EMAIL not configured — cannot send reminders.');
            return self::FAILURE;
        }

        foreach ($posts as $post) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $to)
                    ->notify(new PostScheduleReminderNotification($post));
                $post->update(['reminder_sent_at' => now()]);
                $this->info("  ✓ Reminder sent for Post #{$post->id} → {$to}");
            } catch (\Throwable $e) {
                Log::warning('SendScheduleReminders failed', ['post_id' => $post->id, 'e' => $e->getMessage()]);
                $this->error("  ✗ Post #{$post->id} — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
