<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the two-factor contract for users who require it:
 *
 *  1. authenticator (TOTP) setup must be confirmed;
 *  2. the current session must have passed a TOTP challenge recently.
 *
 * Case staff always require TOTP. A custom administrative role can opt in.
 * The verification timestamp lives in the session, so logging out or expiring
 * the session always requires a fresh code.
 */
class CaseTotpMiddleware
{
    public const SESSION_KEY = 'case.totp_verified_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! $user->requiresTotp()) {
            return $next($request);
        }

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.cases.totp.setup');
        }

        $verifiedAt = (int) $request->session()->get(self::SESSION_KEY, 0);
        $ttl = (int) config('reporting.totp_session_minutes', 480) * 60;

        if ($verifiedAt <= 0 || $verifiedAt + $ttl < now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->guest(route('admin.cases.totp.challenge'));
        }

        return $next($request);
    }
}
