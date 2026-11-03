<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry guard for the whole admin area. A user may enter if they manage the
 * CMS, hold case permissions, or administer users and roles. The CmsMiddleware
 * and CaseTotpMiddleware guards narrow access further.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->is_active || ! $user->canAccessAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
