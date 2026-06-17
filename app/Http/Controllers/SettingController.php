<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Global settings shown in the main /settings page. Platform connections
     * (Instagram, Facebook, YouTube) are now PER-CLIENT, managed from each
     * client's own Settings page. App-level OAuth/dev credentials (Meta App,
     * Google) live in .env.
     */
    private const MAIN_GROUPS = ['ai', 'app'];

    public function index()
    {
        $stored = Setting::map();

        $groups = [];
        foreach (Setting::definitions() as $key => [$configPath, $group, $encrypt, $label, $secret]) {
            if (! in_array($group, self::MAIN_GROUPS, true)) {
                continue; // hidden from main settings (per-client / .env managed)
            }

            $current = $stored[$key] ?? config($configPath);

            $groups[$group][] = [
                'key'    => $key,
                'label'  => $label,
                'secret' => $secret,
                // Never echo secret values back into the page — only whether one is set.
                'value'  => $secret ? '' : (string) $current,
                'isSet'  => $current !== null && $current !== '',
            ];
        }

        return view('settings', ['groups' => $groups, 'tokenStatus' => []]);
    }

    public function update(Request $request)
    {
        foreach (Setting::definitions() as $key => [$configPath, $group, $encrypt, $label, $secret]) {
            // Only touch fields actually rendered/submitted — never wipe a stored
            // value just because its group isn't shown on this page.
            if (! $request->exists($key)) {
                continue;
            }

            $input = $request->input($key);

            // Blank secret field → keep the stored value (don't wipe it).
            if ($secret && ($input === null || $input === '')) {
                continue;
            }

            Setting::put($key, $input, $encrypt, $group);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
