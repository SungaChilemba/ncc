<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * CMS routes are only available to users with a CMS role. Case-only staff must
 * never be able to browse website content through the admin area.
 */
class CmsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->canManageCms()) {
            abort(403);
        }

        return $next($request);
    }
}
