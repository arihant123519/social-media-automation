<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'scope',
        'start_date',
        'end_date',
        'assigned_to',
        'long_video',
        'short_video',
        'story',
        'photo',
        'reels',
        'status',
        'notes',
    ];

    /**
     * Relationship: Scope belongs to Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Optional: Scope label accessor (nice UI ke liye)
     */
    public function getScopeLabelAttribute()
    {
        return ucfirst($this->scope);
    }

    /**
     * Optional: Total monthly content count
     */
    public function getTotalContentAttribute()
    {
        return $this->long_video
            + $this->short_video
            + $this->story
            + $this->photo
            + $this->reels;
    }
}
