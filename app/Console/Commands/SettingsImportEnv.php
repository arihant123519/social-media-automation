<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SettingsImportEnv extends Command
{
    protected $signature = 'settings:import-env {--force : Overwrite settings that already exist}';

    protected $description = 'Seed the settings table from current config()/.env values (one-time migration helper)';

    public function handle(): int
    {
        $existing = Setting::map();
        $imported = 0;
        $skipped  = 0;

        foreach (Setting::definitions() as $key => [$configPath, $group, $encrypt, $label, $secret]) {
            if (! $this->option('force') && array_key_exists($key, $existing)) {
                $skipped++;
                continue;
            }

            $value = config($configPath);
            if ($value === null || $value === '') {
                $skipped++;
                continue;
            }

            Setting::put($key, (string) $value, $encrypt, $group);
            $this->line("  imported: <info>{$key}</info>" . ($secret ? ' (encrypted)' : ''));
            $imported++;
        }

        $this->info("Done. Imported {$imported}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
