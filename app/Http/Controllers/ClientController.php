<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\ClientScope;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
   public function index()
    {
        $clients = Client::with('team:id,name')->get();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        $users = User::where('is_active', 1)
                     ->where('role_id', '!=', 1)
                     ->orderBy('name')
                     ->get();

        return view('clients.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:191|unique:clients,email',
            'phone'    => 'required|string|max:20',
            'slug'     => 'required|string|max:191|unique:clients,slug',
            'industry' => 'required|in:dermatologist,ivf,other',
            'brand_voice' => 'nullable|string|max:4000',
            'brand_tone'  => 'nullable|string|max:120',
            'city'     => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'status'   => 'required|in:active,inactive',
            'user_id'  => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $validated['team_id']    = $user->team_id ?? null;
        $validated['created_by'] = Auth::id();

        Client::create($validated);

        return redirect()->route('clients.index')
                         ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['creator:id,name', 'team:id,name', 'user:id,name,email']);

        // Content scope plans (active + superseded), newest first
        $scopes = ClientScope::where('client_id', $client->id)
            ->orderByDesc('start_date')->get();

        // All posts for this client
        $posts = Post::where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        // Headline stats
        $stats = [
            'total'     => $posts->count(),
            'published' => $posts->where('publish_status', 'published')->count(),
            'scheduled' => $posts->where('publish_status', 'scheduled')->count(),
            'pending'   => $posts->whereIn('publish_status', ['ready', 'not_ready'])->count()
                          + $posts->whereNull('publish_status')->count(),
            'failed'    => $posts->where('publish_status', 'failed')->count(),
            'youtube'   => $posts->where('scope', 0)->count(),
            'instagram' => $posts->where('scope', 1)->count(),
        ];

        // Posts split by platform for the scope-wise listing
        $ytPosts = $posts->where('scope', 0)->values();
        $igPosts = $posts->where('scope', 1)->values();

        return view('clients.show', compact('client', 'scopes', 'posts', 'stats', 'ytPosts', 'igPosts'));
    }

    /**
     * Per-client Settings hub: connect/maintain this client's own
     * Instagram, Facebook and YouTube accounts (OAuth — tokens auto-generated
     * and long-lived). App-level dev credentials (Meta App, Google) live in .env.
     */
    public function settings(Client $client)
    {
        $metaReady   = filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret'));
        $googleReady = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));

        $meta = [
            'app_id'     => (string) config('services.meta.app_id'),
            'config_id'  => (string) config('services.meta.config_id'),
            'version'    => (string) config('services.meta.version', 'v19.0'),
            'has_secret' => filled(config('services.meta.app_secret')),
        ];

        return view('clients.settings', compact('client', 'metaReady', 'googleReady', 'meta'));
    }

    /**
     * Save the shared Meta App credentials (App ID / Secret / Login Config ID)
     * used by the Facebook-Login authorization flow. Stored globally in settings.
     */
    public function saveMetaConfig(Request $request, Client $client)
    {
        $data = $request->validate([
            'meta_app_id'     => 'nullable|string|max:64',
            'meta_app_secret' => 'nullable|string|max:128',
            'meta_config_id'  => 'nullable|string|max:64',
        ]);

        \App\Models\Setting::put('meta_app_id', $data['meta_app_id'] ?? '', false, 'meta');
        \App\Models\Setting::put('meta_config_id', $data['meta_config_id'] ?? '', false, 'meta');
        // Only overwrite the secret when a new value is provided (blank = keep).
        if (filled($data['meta_app_secret'] ?? null)) {
            \App\Models\Setting::put('meta_app_secret', $data['meta_app_secret'], true, 'meta');
        }

        return redirect()->route('clients.settings', $client)->with('success', 'Meta App credentials saved.');
    }

    public function edit(Client $client)
    {
        $users = User::where('is_active', 1)
                     ->where('role_id', '!=', 1)
                     ->orderBy('name')
                     ->get();

        // Current active plan per platform (the one not yet superseded)
        $ytScope = ClientScope::where('client_id', $client->id)->where('scope', 0)
                    ->whereNull('end_date')->orderByDesc('start_date')->first();
        $igScope = ClientScope::where('client_id', $client->id)->where('scope', 1)
                    ->whereNull('end_date')->orderByDesc('start_date')->first();

        return view('clients.edit', compact('client', 'users', 'ytScope', 'igScope'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => ['required', 'email', 'max:191', Rule::unique('clients', 'email')->ignore($client->id)],
            'phone'    => 'required|string|max:20',
            'slug'     => ['required', 'string', 'max:191', Rule::unique('clients', 'slug')->ignore($client->id)],
            'industry' => 'required|in:dermatologist,ivf,other',
            'brand_voice' => 'nullable|string|max:4000',
            'brand_tone'  => 'nullable|string|max:120',
            'city'     => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'status'   => 'required|in:active,inactive',
            'user_id'  => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $validated['team_id'] = $user->team_id ?? null;

        $client->update($validated);

        return redirect()->route('clients.index')
                         ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client deleted.']);
        }

        return redirect()->route('clients.index')
                         ->with('success', 'Client deleted successfully.');
    }



    // scope 
    public function scope()
    {
        $clients = Client::where('status', 'active')->with('team:id,name')->orderBy('name')->get();
        return view('clients.scope',compact('clients'));
    }

    /**
     * Close every active plan for a client+platform so a new one can supersede them.
     * Sets end_date to the day before the new start, then wipes the old plans' FUTURE
     * slots (from the new start onward) so the new plan distributes fresh:
     *   - empty pending slots are deleted
     *   - slots holding a not-yet-published post are unscheduled and removed
     *   - slots with a published post are kept (live work is never destroyed)
     * Past dates (before the new start) are untouched.
     */
    private function supersedeActiveScopes(int $clientId, int $scopeType, \Carbon\Carbon $newStart): void
    {
        $cutover = $newStart->copy()->subDay()->endOfDay();

        // Cap EVERY plan (active, or closed but still ending after the cutover) so none
        // generate slots on/after the new start — prevents overlap with the new plan.
        ClientScope::where('client_id', $clientId)
            ->where('scope', $scopeType)
            ->where(function ($q) use ($cutover) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $cutover);
            })
            ->update(['end_date' => $cutover]);

        // Clear future slots across ALL of this client+platform's plans (old & new),
        // not just one scope row — scheduled posts may be linked to a superseded scope.
        $this->clearFutureSlots($clientId, $scopeType, $newStart);
    }

    /**
     * Remove a client+platform's future slots (>= $fromDate) so a new distribution starts
     * clean. Spans every plan version (any client_scope_id). Unschedules non-published
     * posts on those dates; keeps slots that hold a published (live) post.
     */
    private function clearFutureSlots(int $clientId, int $scopeType, \Carbon\Carbon $fromDate): void
    {
        $logs = \App\Models\PostLog::with('posts')
            ->where('client_id', $clientId)
            ->where('scope', $scopeType)
            ->whereDate('scheduled_date', '>=', $fromDate)
            ->get();

        foreach ($logs as $log) {
            $hasPublished = false;

            foreach ($log->posts as $p) {
                if ($p->publish_status === 'published') {
                    $hasPublished = true;
                    continue;                       // never touch live work
                }
                // Unschedule + detach future scheduled/ready/draft posts
                $p->update([
                    'publish_status'       => 'ready',
                    'scheduled_publish_at' => null,
                    'reminder_sent_at'     => null,
                    'post_log_id'          => null,
                ]);
            }

            // Drop the now-empty future slot so the new plan can redistribute
            if (! $hasPublished) {
                $log->delete();
            }
        }
    }

    public function scopestore(Request $request)
    {
        
        $data = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'scope'       => 'required|in:0,1',
            'start_date'  => 'required|date',
            'long_video'  => 'nullable|numeric|min:0',
            'short_video' => 'nullable|numeric|min:0',
            'reels' => 'nullable|numeric|min:0',
            'story' => 'nullable|numeric|min:0',
            'photo' => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

      
        if ($data['scope'] == 0) {
            $data['reels'] = 0;
            $data['story'] = 0;
            $data['photo'] = 0;
        }
        if ($data['scope'] == 1) {
            $data['long_video'] = 0;
            $data['short_video'] = 0;
        }
        // Default values (safe side)
        $data['long_video']  = $data['long_video'] ?? 0;
        $data['short_video'] = $data['short_video'] ?? 0;
        $data['reels']       = $data['reels'] ?? 0;
        $data['story']       = $data['story'] ?? 0;
        $data['photo']       = $data['photo'] ?? 0;

        // Supersede any existing active plan for this client+platform so plans don't overlap.
        $this->supersedeActiveScopes((int) $data['client_id'], (int) $data['scope'], \Carbon\Carbon::parse($data['start_date']));

        ClientScope::create($data);
        return redirect()
            ->route('clients.scope')
            ->with('success', 'Scope saved successfully 👍');
    }

    /**
     * Version a client's content scope plan.
     *
     * Instead of overwriting, the current active plan is "closed" (end_date set to the
     * day before the new plan starts) and a fresh plan row is created from the new start
     * date. The calendar then shows old dates with the old cadence and new dates with the
     * new cadence — previous data is never altered.
     *
     * POST /clients/{client}/scope-update
     */
    public function scopeUpdate(Request $request, Client $client)
    {
        $data = $request->validate([
            'scope'       => 'required|in:0,1',
            'start_date'  => 'required|date',
            'long_video'  => 'nullable|numeric|min:0',
            'short_video' => 'nullable|numeric|min:0',
            'reels'       => 'nullable|numeric|min:0',
            'story'       => 'nullable|numeric|min:0',
            'photo'       => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $scopeType = (int) $data['scope'];
        $newStart  = \Carbon\Carbon::parse($data['start_date'])->startOfDay();

        // Normalise counts to the relevant platform
        $counts = [
            'long_video'  => $scopeType === 0 ? (int) ($data['long_video']  ?? 0) : 0,
            'short_video' => $scopeType === 0 ? (int) ($data['short_video'] ?? 0) : 0,
            'reels'       => $scopeType === 1 ? (int) ($data['reels'] ?? 0) : 0,
            'story'       => $scopeType === 1 ? (int) ($data['story'] ?? 0) : 0,
            'photo'       => $scopeType === 1 ? (int) ($data['photo'] ?? 0) : 0,
        ];

        $current = ClientScope::where('client_id', $client->id)
            ->where('scope', $scopeType)
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->first();

        $platform = $scopeType === 0 ? 'YouTube' : 'Instagram';

        // CASE 1 — Correction: new start is on/before the current plan's start.
        // This is a fix to the just-created plan, not a new version. Update it in place
        // so same-day re-edits work (no "must be after" error).
        if ($current && $newStart->lte(\Carbon\Carbon::parse($current->start_date)->startOfDay())) {
            $cutover = $newStart->copy()->subDay()->endOfDay();

            // Cap every OTHER plan (active or ending after cutover) so only $current owns
            // dates from the new start — prevents overlapping slots from legacy/dupe scopes.
            ClientScope::where('client_id', $client->id)
                ->where('scope', $scopeType)
                ->where('id', '!=', $current->id)
                ->where(function ($q) use ($cutover) {
                    $q->whereNull('end_date')->orWhere('end_date', '>', $cutover);
                })
                ->update(['end_date' => $cutover]);

            $current->update(array_merge(['start_date' => $newStart->toDateString(), 'notes' => $data['notes'] ?? $current->notes], $counts));

            // Wipe future slots across all plan versions so the new counts redistribute fresh
            $this->clearFutureSlots($client->id, $scopeType, $newStart);

            return redirect()
                ->route('clients.edit', $client->id)
                ->with('success', "{$platform} plan updated (effective {$newStart->format('d M Y')}). Future slots redistributed.");
        }

        // CASE 2 — Versioning: new start is after the current plan → keep history, new plan forward.
        $this->supersedeActiveScopes($client->id, $scopeType, $newStart);

        ClientScope::create(array_merge([
            'client_id'  => $client->id,
            'scope'      => $scopeType,
            'start_date' => $newStart->toDateString(),
            'notes'      => $data['notes'] ?? null,
        ], $counts));

        return redirect()
            ->route('clients.edit', $client->id)
            ->with('success', "{$platform} scope plan updated. New plan starts " . $newStart->format('d M Y') . " — previous dates are preserved.");
    }
}