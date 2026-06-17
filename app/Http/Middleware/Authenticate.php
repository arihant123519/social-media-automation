<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login')->withErrors(['session' => 'Please log in to continue.']);
        }

        $user = Auth::user()->load(['role', 'team']);

        if (! $user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your account has been disabled.']);
        }

        if (! $user->role || ! $user->role->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your role is no longer active.']);
        }

        if ($user->team_id && (! $user->team || ! $user->team->is_active)) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['session' => 'Your team is no longer active.']);
        }

        return $next($request);
    }
}