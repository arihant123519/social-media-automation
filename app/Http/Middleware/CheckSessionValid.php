<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // If session expired but user still tries accessing protected routes
        if (!session()->has('_token')) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['session' => 'Your session has expired. Please login again.']);
        }

        return $next($request);
    }
}
