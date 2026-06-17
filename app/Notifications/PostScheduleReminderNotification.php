<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to the post owner one day before a scheduled post auto-publishes.
 */
class PostScheduleReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
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
        $when       = $this->post->scheduled_publish_at?->format('l, j M Y \a\t g:i A');
        $owner      = $this->post->user?->name ?? 'there';

        return (new MailMessage)
            ->subject("Upcoming auto-publish tomorrow — {$typeLabel} for {$clientName}")
            ->greeting("Hello {$owner},")
            ->line("This is a friendly reminder that the following content is scheduled to publish automatically tomorrow.")
            ->line("**Client:** {$clientName}")
            ->line("**Content type:** {$typeLabel}")
            ->line("**Platform:** {$platform}")
            ->line("**Scheduled for:** {$when}")
            ->action('Review on Calendar', $this->calendarUrl())
            ->line('No action is required if everything looks good — it will publish automatically. To make changes, please do so before the scheduled time.')
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
