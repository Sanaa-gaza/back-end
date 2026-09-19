<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureAccountIsActive
{
    /**
     * Blocks a suspended account from every protected route, even if it
     * still holds a valid (not-yet-revoked) token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status === 'suspended') {
            throw new AccessDeniedHttpException('تم إيقاف هذا الحساب.');
        }

        return $next($request);
    }
}
