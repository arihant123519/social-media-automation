<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLog;
use App\Services\PostPublisher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublishController extends Controller
{
    public function __construct(private PostPublisher $publisher) {}

    /**
     * Publish an approved post NOW.
     *
     * POST /posts/{post}/publish
     */
    public function publish(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $this->publisher->publish($post);

        if (! $result['success']) {
            $code = str_contains((string) $result['error'], 'not approved') ? 422 : 500;
            return response()->json(['success' => false, 'error' => $result['error']], $code);
        }

        $winner = $result['winner'];
        $log    = $result['log'] ?? null;

        return response()->json([
            'success'           => true,
            'status'            => $result['status'] ?? 'published',
            'dry_run'           => $result['dry_run'] ?? false,
            'message'           => $result['message'] ?? null,
            'external_post_id'  => $result['external_post_id'] ?? null,
            'external_url'      => $result['external_url'] ?? null,
            'calendar'          => $this->calendarPayload($post, $log),
            'published_attempt' => [
                'number'          => $winner->attempt_number,
                'score'           => $winner->score,
                'caption_preview' => mb_substr((string) $winner->caption, 0, 140),
                'hashtags'        => $winner->hashtags,
            ],
        ]);
    }

    /**
     * Save an approved post so it appears on the calendar, ready to schedule.
     * Ensures the post is linked to a calendar slot (PostLog).
     *
     * POST /posts/{post}/save
     */
    public function save(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        if ($post->final_status !== 'approved') {
            return response()->json(['success' => false, 'error' => 'Only approved posts can be saved.'], 422);
        }

        // Link to (or create) the calendar slot so it shows up on the calendar
        if (! $post->post_log_id && $post->scheduled_date && $post->client_scope_id) {
            $log = PostLog::firstOrCreate(
                [
                    'client_scope_id' => $post->client_scope_id,
                    'post_type'       => $post->post_type,
                    'scheduled_date'  => $post->scheduled_date,
                ],
                [
                    'client_id' => $post->client_id,
                    'scope'     => $post->scope,
                    'status'    => 'pending',
                ]
            );
            $post->post_log_id = $log->id;
        }

        // Move from approved → ready (awaiting scheduling), unless already further along
        if (! in_array($post->publish_status, ['scheduled', 'publishing', 'published'], true)) {
            $post->publish_status = 'ready';
        }
        $post->save();

        $date = $post->scheduled_date;
        $calendarUrl = $date
            ? route('calendar.index', ['client_id' => $post->client_id, 'month' => $date->month, 'year' => $date->year])
            : route('calendar.index', ['client_id' => $post->client_id]);

        return response()->json([
            'success'        => true,
            'calendar_url'   => $calendarUrl,
            'on_calendar'    => (bool) $post->post_log_id,
            'scheduled_date' => $date?->format('l, j M Y'),
        ]);
    }

    /**
     * Schedule an approved post to auto-publish at a future datetime.
     *
     * POST /posts/{post}/schedule  { scheduled_publish_at: "2026-05-25 18:30" }
     */
    public function schedule(Request $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'scheduled_publish_at' => 'required|date|after:now',
        ]);

        $threshold = PostPublisher::approvalScore();
        if ($post->final_status !== 'approved') {
            return response()->json(['success' => false, 'error' => 'This post is not approved yet — score an attempt first.'], 422);
        }
        if ((int) $post->best_score < $threshold) {
            return response()->json([
                'success' => false,
                'error'   => "Score {$post->best_score} is below the current approval threshold of {$threshold}. Lower the threshold in /settings or improve the post.",
            ], 422);
        }

        if (in_array($post->publish_status, ['publishing', 'published'], true)) {
            return response()->json(['success' => false, 'error' => "Post is already {$post->publish_status}."], 409);
        }

        $when = Carbon::parse($data['scheduled_publish_at']);

        $post->update([
            'scheduled_publish_at' => $when,
            'reminder_sent_at'     => null,   // reset so the reminder fires for the new date
            'publish_status'       => 'scheduled',
            'publish_error'        => null,
        ]);

        return response()->json([
            'success'  => true,
            'status'   => 'scheduled',
            'when'     => $when->format('Y-m-d H:i'),
            'when_human' => $when->format('l, j M Y \a\t g:i A'),
        ]);
    }

    /**
     * Reopen a slot: reset a dry-run / completed post back to a schedulable state
     * and mark its calendar slot pending again. Real published posts are NOT reopened.
     *
     * POST /posts/{post}/reopen
     */
    public function reopen(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        // A real, live publish should not be reopened (it's already public).
        if ($post->publish_status === 'published' && $post->external_url) {
            return response()->json(['success' => false, 'error' => 'This post is already live and cannot be reopened.'], 422);
        }

        $post->update([
            'publish_status'       => 'ready',
            'scheduled_publish_at' => null,
            'reminder_sent_at'     => null,
            'published_at'         => null,
            'external_post_id'     => null,
            'external_url'         => null,
            'publish_error'        => null,
        ]);

        // Revert the linked calendar slot to pending
        if ($post->post_log_id) {
            PostLog::where('id', $post->post_log_id)->update(['status' => 'pending']);
        }

        return response()->json(['success' => true, 'status' => 'ready']);
    }

    /**
     * Cancel a scheduled publish (revert to ready/approved).
     *
     * POST /posts/{post}/unschedule
     */
    public function unschedule(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        if ($post->publish_status !== 'scheduled') {
            return response()->json(['success' => false, 'error' => 'Post is not scheduled.'], 422);
        }

        $post->update([
            'scheduled_publish_at' => null,
            'reminder_sent_at'     => null,
            'publish_status'       => 'ready',
        ]);

        return response()->json(['success' => true, 'status' => 'ready']);
    }

    private function calendarPayload(Post $post, ?PostLog $log): ?array
    {
        if (! $log) return null;

        return [
            'slot_date'       => $log->scheduled_date->format('Y-m-d'),
            'slot_date_human' => $log->scheduled_date->format('l, j M Y'),
            'post_type'       => $log->post_type,
            'status'          => $log->status,
            'view_url'        => route('calendar.index', [
                'client_id' => $post->client_id,
                'month'     => $log->scheduled_date->month,
                'year'      => $log->scheduled_date->year,
            ]),
        ];
    }
}
