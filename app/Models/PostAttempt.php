<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostAttempt extends Model
{
    protected $fillable = [
        'post_id', 'attempt_number', 'file_path', 'mime', 'file_size',
        'caption', 'hashtags',
        'score', 'ai_feedback', 'suggestions',
    ];

    protected $casts = [
        'ai_feedback'    => 'array',
        'suggestions'    => 'array',
        'attempt_number' => 'integer',
        'score'          => 'integer',
        'file_size'      => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
