<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureEmailIsVerified
{
    /**
     * Defense in depth: Sanctum tokens are only ever issued after
     * verification (§6.1), but this guards against a token surviving a
     * later change to email_verified_at (e.g. an admin action). See §6.5.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->email_verified_at) {
            throw new AccessDeniedHttpException('يجب تأكيد البريد الإلكتروني للوصول لهذا المسار.');
        }

        return $next($request);
    }
}
