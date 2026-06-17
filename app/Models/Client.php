<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'slug',
        'industry',
        'brand_voice',
        'brand_tone',
        'city',
        'zip',
        'status',
        'user_id',
        'team_id',
        'created_by',
        'auto_publish_enabled',
        'ig_access_token', 'ig_business_id',
        'yt_refresh_token', 'yt_access_token', 'yt_token_expires_at', 'yt_channel_id',
        'fb_page_id', 'fb_page_token',
        'linkedin_token', 'linkedin_author_urn',
    ];

    protected $casts = [
        'auto_publish_enabled' => 'boolean',
        'yt_token_expires_at'  => 'datetime',
        // Encrypt sensitive tokens at rest (DB stores ciphertext, app decrypts transparently)
        'ig_access_token'      => 'encrypted',
        'yt_refresh_token'     => 'encrypted',
        'yt_access_token'      => 'encrypted',
        'fb_page_token'        => 'encrypted',
        'linkedin_token'       => 'encrypted',
    ];

    protected $hidden = [
        'ig_access_token', 'yt_refresh_token', 'yt_access_token',
        'fb_page_token', 'linkedin_token',
    ];

    /**
     * #12 — Build a brand-voice context block for AI prompts.
     * Returns '' when the client has no brand voice configured, so prompts
     * stay clean and generic in that case.
     */
    public function brandVoiceBlock(): string
    {
        $voice = trim((string) $this->brand_voice);
        $tone  = trim((string) $this->brand_tone);
        if ($voice === '' && $tone === '') {
            return '';
        }

        $lines = ["BRAND VOICE — write everything to match this client's identity:"];
        $lines[] = "- Client: {$this->name}" . ($this->industry ? " ({$this->industry})" : '');
        if ($tone !== '')  $lines[] = "- Tone: {$tone}";
        if ($voice !== '') $lines[] = "- Brand voice / do's & don'ts:\n{$voice}";
        $lines[] = "Stay on-brand: mirror this tone in hooks, captions and CTAs. Never contradict the do's & don'ts above.";

        return "\n" . implode("\n", $lines) . "\n";
    }

    public function hasYouTubeConnected(): bool
    {
        return ! empty($this->yt_refresh_token);
    }

    public function hasInstagramConnected(): bool
    {
        return ! empty($this->ig_access_token) && ! empty($this->ig_business_id);
    }

    public function hasFacebookConnected(): bool
    {
        return ! empty($this->fb_page_token) && ! empty($this->fb_page_id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Who created this client record
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopes()
    {
        return $this->hasMany(ClientScope::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}