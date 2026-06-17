<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * DB-stored settings override config() values at runtime. .env stays the
     * fallback: only non-empty stored values are applied. Guarded so it's safe
     * before the migration runs and during any DB/decrypt failure.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $values = Setting::map();
            if (empty($values)) {
                return;
            }

            foreach (Setting::definitions() as $key => [$configPath]) {
                $value = $values[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                config([$configPath => $value]);
            }

            // Timezone override alone is too late — the framework already set it
            // from .env during bootstrap. Re-apply for now()/Carbon.
            if (! empty($values['app_timezone'])) {
                date_default_timezone_set($values['app_timezone']);
            }
        } catch (\Throwable) {
            // DB unavailable / corrupt setting → silently fall back to .env.
        }
    }
}
