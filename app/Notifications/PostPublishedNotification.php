<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to the post owner after a publish attempt (success, dry-run, or failure).
 */
class PostPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public bool $success,
        public ?string $error = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName    = config('app.name');
        $platform   = $this->post->scope === 0 ? 'YouTube' : 'Instagram';
        $clientName = $this->post->client?->name ?? 'the client';
        $typeLabel  = $this->typeLabel();
        $isDryRun   = $this->post->publish_status === 'dry_run';
        $owner      = $this->post->user?->name ?? 'there';
        $when       = optional($this->post->published_at)->format('d M Y, g:i A') ?? now()->format('d M Y, g:i A');

        // ── Failure ──
        if (! $this->success) {
            return (new MailMessage)
                ->subject("Publishing failed — {$typeLabel} for {$clientName}")
                ->greeting("Hello {$owner},")
                ->line("Unfortunately, we were unable to publish the following content to **{$platform}**.")
                ->line("**Client:** {$clientName}")
                ->line("**Content type:** {$typeLabel}")
                ->line("**Reason:** " . ($this->error ?: 'Unknown error'))
                ->action('Review on Calendar', $this->calendarUrl())
                ->line('Please review the post and try publishing again.')
                ->salutation("Regards,  \n{$appName}");
        }

        // ── Dry-run ──
        if ($isDryRun) {
            return (new MailMessage)
                ->subject("Test publish completed — {$typeLabel} for {$clientName}")
                ->greeting("Hello {$owner},")
                ->line("Your **{$typeLabel}** for **{$clientName}** was processed in test (dry-run) mode.")
                ->line('No live post was created because the platform account is not yet connected.')
                ->action('Open Calendar', $this->calendarUrl())
                ->line('Connect the platform account to publish for real.')
                ->salutation("Regards,  \n{$appName}");
        }

        // ── Success ──
        $mail = (new MailMessage)
            ->subject("Published successfully — {$typeLabel} for {$clientName}")
            ->greeting("Hello {$owner},")
            ->line("Great news — your content has been published successfully to **{$platform}**.")
            ->line("**Client:** {$clientName}")
            ->line("**Content type:** {$typeLabel}")
            ->line("**Quality score:** {$this->post->best_score}/100")
            ->line("**Published on:** {$when}");

        if ($this->post->external_url) {
            $mail->action('View Live Post', $this->post->external_url);
        }

        return $mail
            ->line('Thank you for using ' . $appName . '.')
            ->salutation("Regards,  \n{$appName}");
    }

    private function typeLabel(): string
    {
        return match ($this->post->post_type) {
            'long_video'  => 'YouTube Long Video',
            'short_video' => 'YouTube Short',
            'reels'       => 'Instagram Reel',
            'photo'       => 'Instagram Photo',
            'story'       => 'Instagram Story',
            default       => ucfirst((string) $this->post->post_type),
        };
    }

    private function calendarUrl(): string
    {
        try {
            return route('calendar.index', ['client_id' => $this->post->client_id]);
        } catch (\Throwable) {
            return config('app.url');
        }
    }
}
