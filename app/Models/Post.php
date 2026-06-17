<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'client_id', 'user_id', 'scope', 'post_type',
        'keyword', 'scheduled_date', 'client_scope_id', 'post_log_id',
        'trending_ref_id', 'trending_ref_meta',
        'best_score', 'final_status',
        'scheduled_publish_at', 'reminder_sent_at', 'published_at',
        'external_post_id', 'external_url',
        'publish_status', 'publish_error',
    ];

    protected $casts = [
        'trending_ref_meta'    => 'array',
        'scope'                => 'integer',
        'best_score'           => 'integer',
        'scheduled_date'       => 'date',
        'scheduled_publish_at' => 'datetime',
        'reminder_sent_at'     => 'datetime',
        'published_at'         => 'datetime',
    ];

    public function postLog()
    {
        return $this->belongsTo(PostLog::class);
    }

    public function clientScope()
    {
        return $this->belongsTo(ClientScope::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attempts()
    {
        return $this->hasMany(PostAttempt::class)->orderBy('attempt_number');
    }

    public function latestAttempt()
    {
        return $this->hasOne(PostAttempt::class)->latestOfMany('attempt_number');
    }
}
