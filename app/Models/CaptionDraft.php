<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaptionDraft extends Model
{
    protected $fillable = [
        'client_id', 'scope', 'post_type', 'scheduled_date', 'theme',
        'keyword', 'caption', 'hashtags', 'status', 'generated_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'generated_at'   => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function platformLabel(): string
    {
        return match ((int) $this->scope) {
            0 => 'YouTube',
            1 => 'Instagram',
            2 => 'Facebook',
            3 => 'LinkedIn',
            default => 'Social',
        };
    }
}
