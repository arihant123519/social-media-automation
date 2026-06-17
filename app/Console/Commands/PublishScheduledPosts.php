<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Publishes posts whose scheduled time has arrived.
 * Runs every minute via the scheduler (routes/console.php).
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Publish posts whose scheduled_publish_at time has passed';

    public function handle(PostPublisher $publisher): int
    {
        $due = Post::where('publish_status', 'scheduled')
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->orderBy('scheduled_publish_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No posts due for publishing.');
            return self::SUCCESS;
        }

        $this->info("Found {$due->count()} post(s) due. Publishing...");

        foreach ($due as $post) {
            $this->line("→ Post #{$post->id} (" . ($post->scope === 0 ? 'YouTube' : 'Instagram') . ", {$post->post_type})");

            try {
                $result = $publisher->publish($post);

                if ($result['success']) {
                    $label = ($result['dry_run'] ?? false) ? 'DRY-RUN' : 'PUBLISHED';
                    $this->info("  ✓ {$label}" . (isset($result['external_url']) ? ' — ' . $result['external_url'] : ''));
                } else {
                    $this->error("  ✗ FAILED — {$result['error']}");
                }
            } catch (\Throwable $e) {
                Log::error('PublishScheduledPosts exception', ['post_id' => $post->id, 'e' => $e->getMessage()]);
                $post->update(['publish_status' => 'failed', 'publish_error' => $e->getMessage()]);
                $this->error("  ✗ EXCEPTION — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
