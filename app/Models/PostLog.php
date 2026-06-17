<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostLog extends Model
{
    protected $fillable = [
        'client_scope_id',
        'client_id',
        'scope',
        'post_type',
        'scheduled_date',
        'status',
        'note',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function clientScope()
    {
        return $this->belongsTo(ClientScope::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function post()
    {
        return $this->hasOne(Post::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * The post that best represents this slot when several were created for it.
     * Priority: published → scheduled → approved/ready → latest attempt.
     */
    public function bestPost(): ?Post
    {
        $posts = $this->relationLoaded('posts') ? $this->posts : $this->posts()->get();
        if ($posts->isEmpty()) return null;

        $rank = function (Post $p): int {
            return match (true) {
                $p->publish_status === 'published' => 6,
                $p->publish_status === 'dry_run'   => 5,   // test-mode publish counts as done
                $p->publish_status === 'scheduled' => 4,
                $p->final_status   === 'approved'  => 3,
                $p->publish_status === 'failed'    => 2,
                default                            => 1,
            };
        };

        // Composite key: rank (high first) then latest id — reliable single-key sort.
        return $posts->sortByDesc(fn (Post $p) => sprintf('%d%012d', $rank($p), $p->id))->first();
    }
}
