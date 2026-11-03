<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Maximum failed attempts before the login form is temporarily locked.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds.
     */
    private const DECAY_SECONDS = 60;

    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt([...$credentials, 'is_active' => true], $request->filled('remember'))) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();

            if (! $user->canAccessAdmin()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'You do not have access to the admin area.',
                ]);
            }

            $request->session()->regenerate();

            // Case-only staff land in the case queue. Everyone else goes to the
            // admin dashboard, which forwards them to the area they can use.
            $target = $user->hasCaseAccess()
                && ! $user->canManageCms()
                && ! $user->hasPermission('admin.*')
                ? route('admin.cases.index')
                : route('admin.dashboard');

            return redirect()->intended($target);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
