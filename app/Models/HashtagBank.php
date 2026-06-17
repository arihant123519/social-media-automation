<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HashtagBank extends Model
{
    protected $table = 'hashtag_bank';

    protected $fillable = [
        'specialty', 'tag', 'category', 'performance',
        'avg_reach', 'notes', 'last_reviewed_at',
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
    ];

    /** Performance → sort weight (high first). */
    public const PERF_WEIGHT = ['high' => 3, 'medium' => 2, 'low' => 1];

    /**
     * Normalise a raw tag input to a single "#word" token.
     */
    public static function normalizeTag(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/\s+/', '', $raw);
        $raw = ltrim($raw, '#');
        return $raw === '' ? '' : '#' . $raw;
    }
}
