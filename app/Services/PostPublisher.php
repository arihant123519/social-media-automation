<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostAttempt;
use App\Models\PostLog;
use App\Notifications\PostPublishedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
 
/**
 * Core publish pipeline shared by manual publish (PublishController) and
 * scheduled publish (posts:publish-scheduled command).
 *
 * Handles: winner selection, spelling gate, platform dispatch (IG/YT),
 * status updates, calendar log update, and notifications (email + Slack).
 */
class PostPublisher
{
    /**
     * Min score a post's best attempt must hit before it can be published.
     * Configurable from /settings (DB) → falls back to .env → falls back to 60.
     */
    public static function approvalScore(): int
    {
        $v = (int) config('publishing.approval_score', 60);
        return ($v >= 0 && $v <= 100) ? $v : 60;
    }

    public function __construct(
        private InstagramPublishService $ig,
        private YoutubePublishService $yt,
        private FacebookPublishService $fb,
        private LinkedInPublishService $li,
    ) {}

    /**
     * Publish a post to its target platform.
     *
     * @return array{success: bool, status?: string, error?: string, dry_run?: bool,
     *               winner?: PostAttempt, log?: PostLog, external_url?: ?string,
     *               external_post_id?: ?string, message?: string}
     */
    public function publish(Post $post): array
    {
        $threshold = self::approvalScore();
        if ($post->final_status !== 'approved') {
            return ['success' => false, 'error' => 'Post has not been approved yet — re-upload an attempt to score it.'];
        }
        if ((int) $post->best_score < $threshold) {
            return [
                'success' => false,
                'error'   => "Post's best score is {$post->best_score} but the current approval threshold is {$threshold}. Lower it in /settings or improve the post.",
            ];
        }

        if (in_array($post->publish_status, ['publishing', 'published'], true)) {
            return ['success' => false, 'error' => "Already {$post->publish_status}."];
        }

        // Pick the BEST attempt: highest score, latest attempt_number on ties.
        // reorder() clears the default orderBy('attempt_number') on the relationship.
        $winner = $post->attempts()
            ->reorder()
            ->orderByDesc('score')
            ->orderByDesc('attempt_number')
            ->first();

        if (! $winner) {
            return ['success' => false, 'error' => 'No winning attempt found.'];
        }

        // Spelling gate — the winning attempt must be error-free
        $fb       = is_array($winner->ai_feedback) ? $winner->ai_feedback : [];
        $spellErr = count($fb['parameters']['spelling_grammar']['errors'] ?? []);
        if ($spellErr > 0) {
            return ['success' => false, 'error' => "Cannot publish — {$spellErr} spelling error(s) on the winning attempt (V{$winner->attempt_number})."];
        }

        Log::info('PostPublisher: publishing', [
            'post_id'        => $post->id,
            'winner_attempt' => $winner->attempt_number,
            'winner_score'   => $winner->score,
            'scope'          => $post->scope,
        ]);

        $post->update(['publish_status' => 'publishing', 'publish_error' => null]);

        $result = match ((int) $post->scope) {
            0       => $this->yt->publish($post, $winner),
            1       => $this->ig->publish($post, $winner),
            2       => $this->fb->publish($post, $winner),
            3       => $this->li->publish($post, $winner),
            default => $this->ig->publish($post, $winner),
        };

        // ── Failure ──
        if (! ($result['success'] ?? false)) {
            $post->update([
                'publish_status' => 'failed',
                'publish_error'  => $result['error'] ?? 'Unknown error',
            ]);
            $this->notify($post, false, $result['error'] ?? 'unknown');
            return ['success' => false, 'error' => $result['error'] ?? 'Unknown error', 'winner' => $winner];
        }

        // ── Dry-run (tokens not configured) ──
        if ($result['dry_run'] ?? false) {
            $post->update(['publish_status' => 'dry_run', 'published_at' => now()]);
            $log = $this->updateCalendarLog($post);
            $this->notify($post, true);
            return [
                'success' => true,
                'dry_run' => true,
                'status'  => 'dry_run',
                'winner'  => $winner,
                'log'     => $log,
                'message' => $result['message'] ?? 'Dry run only.',
            ];
        }

        // ── Real publish success ──
        // Clear scheduled_publish_at so the post no longer appears on a future
        // calendar slot — it now belongs to "today" (published_at).
        $post->update([
            'publish_status'       => 'published',
            'published_at'         => now(),
            'scheduled_publish_at' => null,
            'external_post_id'     => $result['external_post_id'] ?? null,
            'external_url'         => $result['external_url'] ?? null,
        ]);
        $log = $this->updateCalendarLog($post);
        $this->notify($post, true);

        return [
            'success'          => true,
            'status'           => 'published',
            'winner'           => $winner,
            'log'              => $log,
            'external_url'     => $post->external_url,
            'external_post_id' => $post->external_post_id,
        ];
    }

    /**
     * Mark the linked calendar PostLog as 'completed'.
     * Resolution: post_log_id → (client,type,scheduled_date) → today → nearest pending.
     */
    public function updateCalendarLog(Post $post): ?PostLog
    {
        try {
            $log = null;

            if ($post->post_log_id) {
                $log = PostLog::find($post->post_log_id);
            }
            if (! $log && $post->scheduled_date) {
                $log = PostLog::where('client_id', $post->client_id)
                    ->where('post_type', $post->post_type)
                    ->whereDate('scheduled_date', $post->scheduled_date)
                    ->first();
            }
            if (! $log) {
                $log = PostLog::where('client_id', $post->client_id)
                    ->where('post_type', $post->post_type)
                    ->whereDate('scheduled_date', Carbon::today())
                    ->first();
            }
            if (! $log) {
                $log = PostLog::where('client_id', $post->client_id)
                    ->where('post_type', $post->post_type)
                    ->where('status', 'pending')
                    ->whereDate('scheduled_date', '>=', Carbon::today())
                    ->orderBy('scheduled_date')
                    ->first();
            }

            if ($log) {
                $log->update([
                    'status' => 'completed',
                    'note'   => trim(($log->note ? $log->note . "\n" : '') . 'Auto-published: ' . ($post->external_url ?: '(dry-run)')),
                ]);
                if (! $post->post_log_id) {
                    $post->update(['post_log_id' => $log->id]);
                }
            }

            return $log;
        } catch (\Throwable $e) {
            Log::warning('updateCalendarLog failed', ['post_id' => $post->id, 'err' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Email the post owner + post to Slack.
     */
    private function notify(Post $post, bool $success, ?string $error = null): void
    {
        // All publish notifications go to the fixed NOTIFY_EMAIL, regardless of post owner
        $to = config('services.notify.email');
        if ($to) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $to)
                    ->notify(new PostPublishedNotification($post, $success, $error));
            } catch (\Throwable $e) {
                Log::warning('PostPublishedNotification failed', ['post_id' => $post->id, 'e' => $e->getMessage()]);
            }
        }

        $this->slackNotify($post, $success, $error);
    }

    private function slackNotify(Post $post, bool $success, ?string $error = null): void
    {
        $token   = (string) config('services.slack.notifications.bot_user_oauth_token');
        $channel = (string) config('services.slack.notifications.channel');
        if ($token === '' || $channel === '') return;

        $platform = ucfirst((string) config('publishing.platforms.' . (int) $post->scope, 'social'));
        $text = $success
            ? "✅ Post #{$post->id} published to {$platform}\n• Score: {$post->best_score}/100\n• Link: " . ($post->external_url ?: '(no url)')
            : "❌ Post #{$post->id} publish FAILED on {$platform}\n• Reason: {$error}";

        try {
            Http::timeout(10)->withToken($token)->post('https://slack.com/api/chat.postMessage', [
                'channel' => $channel,
                'text'    => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('slackNotify failed', ['e' => $e->getMessage()]);
        }
    }
}
