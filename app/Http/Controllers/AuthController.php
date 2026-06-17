<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
 
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $user = User::with(['role', 'team'])
                    ->where('email', $request->email)
                    ->first();

        if (! $user) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        try {
            $passwordMatch = Hash::check($request->password, $user->password);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['email' => 'Something went wrong with password. Please contact admin or reset password.'])
                ->withInput();
        }

        if (! $passwordMatch) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Your account is disabled.'])->withInput();
        }

        if (! $user->role || ! $user->role->is_active) {
            return back()->withErrors(['email' => 'Your role is inactive.'])->withInput();
        }

        if ($user->team_id && (! $user->team || ! $user->team->is_active)) {
            return back()->withErrors(['email' => 'Your team is inactive.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
    
    public function index()
    {
        $user = Auth::user();

        // Supported social platforms ("scopes"). Add LinkedIn etc. here in future.
        $platforms = ['YouTube', 'Instagram'];

        $stats = [
            'clients_total'    => Client::count(),
            'clients_active'   => Client::where('status', 'active')->count(),
            'clients_inactive' => Client::where('status', 'inactive')->count(),
            'platforms'        => $platforms,
            'platforms_count'  => count($platforms),
            'posts_total'      => Post::count(),
            'posts_published'  => Post::where('publish_status', 'published')->count(),
            'posts_scheduled'  => Post::where('publish_status', 'scheduled')->count(),
            'posts_pending'    => Post::whereIn('publish_status', ['ready', 'not_ready'])->orWhereNull('publish_status')->count(),
        ];

        // Today's scheduled posts, grouped by client (for a quick per-client check)
        $todaysPosts = Post::with('client:id,name')
            ->whereDate('scheduled_publish_at', \Carbon\Carbon::today())
            ->orderBy('scheduled_publish_at')
            ->get()
            ->groupBy(fn ($p) => $p->client?->name ?? 'Unknown client');

        $unscheduledPosts = Post::with('client:id,name')
            ->whereNull('scheduled_publish_at')
            ->whereIn('publish_status', ['ready', 'not_ready'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn ($p) => $p->client?->name ?? 'Unknown client');

        return view('dashboard', compact('user', 'stats', 'todaysPosts', 'unscheduledPosts'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
 
        $request->session()->invalidate();
        $request->session()->regenerateToken();
 
        return redirect()->route('login');
    }
}
