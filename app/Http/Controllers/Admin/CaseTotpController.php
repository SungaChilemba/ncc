<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CaseTotpMiddleware;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaseTotpController extends Controller
{
    private const PENDING_SECRET = 'case.totp_pending_secret';

    private const RECOVERY_FLASH = 'case_recovery_codes';

    private const MAX_ATTEMPTS = 5;

    public function showSetup(Request $request)
    {
        $user = $request->user();
        abort_unless($user->requiresTotp(), 403);

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()
                ->route('admin.cases.totp.challenge')
                ->with('status', 'Two-factor authentication is already configured.');
        }

        $secret = $request->session()->get(self::PENDING_SECRET);

        if (! is_string($secret) || $secret === '') {
            $secret = Totp::generateSecret();
            $request->session()->put(self::PENDING_SECRET, $secret);
        }

        return view('admin.cases.totp-setup', [
            'secret' => $secret,
            'formattedSecret' => Totp::formattedSecret($secret),
            'otpauthUri' => Totp::uri($secret, $user->email, 'NCC Admin'),
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresTotp(), 403);

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.dashboard');
        }

        $secret = $request->session()->get(self::PENDING_SECRET);

        if (! is_string($secret) || $secret === '') {
            return redirect()->route('admin.cases.totp.setup');
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! Totp::verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'That code did not match. Check your authenticator app and try again.',
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget(self::PENDING_SECRET);
        $request->session()->put(CaseTotpMiddleware::SESSION_KEY, now()->getTimestamp());
        $request->session()->flash(self::RECOVERY_FLASH, $recoveryCodes);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Two-factor authentication is now active for your account.');
    }

    public function showChallenge(Request $request)
    {
        $user = $request->user();
        abort_unless($user->requiresTotp(), 403);

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.cases.totp.setup');
        }

        $verifiedAt = (int) $request->session()->get(CaseTotpMiddleware::SESSION_KEY, 0);
        $ttl = (int) config('reporting.totp_session_minutes', 480) * 60;

        if ($verifiedAt > 0 && $verifiedAt + $ttl >= now()->getTimestamp()) {
            return redirect()->route('admin.dashboard');
        }

        $request->session()->forget(CaseTotpMiddleware::SESSION_KEY);

        return view('admin.cases.totp-challenge');
    }

    public function challenge(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->requiresTotp(), 403);

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.cases.totp.setup');
        }

        $throttleKey = 'case-totp|'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'code' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $code = (string) $request->input('code');

        if (Totp::verify((string) $user->two_factor_secret, $code)) {
            RateLimiter::clear($throttleKey);

            return $this->completeChallenge($request);
        }

        if ($this->consumeRecoveryCode($user, $code)) {
            RateLimiter::clear($throttleKey);

            return $this->completeChallenge($request)
                ->with('status', 'Recovery code accepted. It cannot be used again.');
        }

        RateLimiter::hit($throttleKey, 60);

        throw ValidationException::withMessages([
            'code' => 'That code was not accepted. Try the current code from your authenticator app.',
        ]);
    }

    private function completeChallenge(Request $request): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put(CaseTotpMiddleware::SESSION_KEY, now()->getTimestamp());

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Recovery codes are stored encrypted at rest. A used code is removed so a
     * screenshot of the list cannot be replayed later.
     */
    private function consumeRecoveryCode($user, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $candidate) {
            if (hash_equals(strtoupper((string) $candidate), $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $count = max(1, (int) config('reporting.totp_recovery_codes', 8));
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4).Str::random(4));
        }

        return $codes;
    }
}
