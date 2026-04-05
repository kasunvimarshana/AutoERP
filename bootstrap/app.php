<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'tenant' => \Modules\Tenant\Infrastructure\Http\Middleware\TenantMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Modules\Core\Domain\Exceptions\NotFoundException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (\Modules\Core\Domain\Exceptions\DomainException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        });
    })
    ->create();
