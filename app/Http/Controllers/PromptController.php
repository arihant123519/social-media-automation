<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\Request;

/**
 * "Prompts" screen — view & edit every AI prompt the app applies.
 *
 * Prompts live in the `prompts` table; the canonical defaults come from
 * {@see \App\Support\PromptRegistry}. On each visit we sync any new defaults in
 * so the list is always complete, then group them for display.
 */
class PromptController extends Controller
{
    public function index()
    {
        Prompt::syncDefaults();

        $groups = Prompt::orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('prompts.index', compact('groups'));
    }

    public function update(Request $request, Prompt $prompt)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:160',
            'description' => 'nullable|string|max:500',
            'template'    => 'required|string|max:20000',
            'is_active'   => 'nullable|boolean',
        ]);

        $prompt->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'template'    => $data['template'],
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('prompts.index')
            ->with('status', "Prompt \"{$prompt->name}\" saved.");
    }

    /** Restore a single prompt's template to its packaged default. */
    public function reset(Prompt $prompt)
    {
        $default = \App\Support\PromptRegistry::defaults()[$prompt->key] ?? null;

        if ($default) {
            $prompt->update([
                'name'        => $default['name'],
                'description' => $default['description'] ?? null,
                'template'    => $default['template'],
                'variables'   => $default['variables'] ?? [],
                'is_active'   => true,
            ]);
        }

        return redirect()->route('prompts.index')
            ->with('status', "Prompt \"{$prompt->name}\" reset to default.");
    }
}
