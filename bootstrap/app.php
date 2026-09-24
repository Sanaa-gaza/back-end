<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (and most PaaS hosts) sit behind a reverse proxy that terminates
        // TLS, so trust its forwarded headers to detect HTTPS/host correctly.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'verified.email' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'active.account' => \App\Http\Middleware\EnsureAccountIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // This is an API-only app with no web login route. Laravel's default
        // guest redirect (ApplicationBuilder) points to route('login'), which
        // doesn't exist here and throws for any unauthenticated non-JSON
        // request. Disable the redirect so it falls through to a clean 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API response follows the {success, data|errors, message} envelope
        // from the Sprint 1 spec — including error responses, which Laravel would
        // otherwise render in its own default shape for these exception types.
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول. الرجاء تسجيل الدخول.',
                    'errors' => [],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء.',
                    'errors' => [],
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'العنصر المطلوب غير موجود.',
                    'errors' => [],
                ], 404);
            }
        });
    })->create();
