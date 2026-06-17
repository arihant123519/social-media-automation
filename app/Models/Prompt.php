<?php

namespace App\Models;

use App\Support\PromptRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A single AI prompt template, editable from the "Prompts" screen.
 *
 * Every prompt the app sends to Gemini lives here. The canonical defaults are
 * declared in {@see PromptRegistry}; the DB row (when present) overrides the
 * default so the team can tweak wording without touching code. Each template
 * uses {{ placeholder }} tokens that callers fill in at render time.
 */
class Prompt extends Model
{
    protected $fillable = ['key', 'name', 'group', 'description', 'template', 'variables', 'is_active'];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public const CACHE_KEY = 'prompt_templates';

    /** [key => template] for every stored prompt, cached until a save busts it. */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->where('is_active', true)
                ->pluck('template', 'key')->all();
        });
    }

    /**
     * Render a prompt by key, substituting {{ name }} tokens with $vars.
     *
     * Falls back to the packaged default template when no (active) DB row
     * exists, so the app never breaks if a row is missing or deactivated.
     */
    public static function render(string $key, array $vars = []): string
    {
        $template = static::map()[$key]
            ?? (PromptRegistry::defaults()[$key]['template'] ?? null);

        if ($template === null) {
            throw new \InvalidArgumentException("Unknown prompt key: {$key}");
        }

        $replace = [];
        foreach ($vars as $name => $value) {
            $value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
            $replace['{{ ' . $name . ' }}'] = $value;
            $replace['{{' . $name . '}}']   = $value;
        }

        return strtr($template, $replace);
    }

    /**
     * Insert any registry-defined prompts that don't yet have a DB row, so the
     * management screen always lists the full, current set. Existing rows (which
     * may carry team edits) are never overwritten.
     */
    public static function syncDefaults(): void
    {
        $existing = static::pluck('key')->all();

        foreach (PromptRegistry::defaults() as $key => $def) {
            if (in_array($key, $existing, true)) {
                continue;
            }
            static::create([
                'key'         => $key,
                'name'        => $def['name'],
                'group'       => $def['group'] ?? 'general',
                'description' => $def['description'] ?? null,
                'template'    => $def['template'],
                'variables'   => $def['variables'] ?? [],
                'is_active'   => true,
            ]);
        }

        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
