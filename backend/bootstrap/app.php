<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant'     => \App\Http\Middleware\TenantMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'abac'       => \App\Http\Middleware\CheckAbacPolicy::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\App\Exceptions\TenantException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        });

        $exceptions->renderable(function (\App\Exceptions\SagaException $e) {
            return response()->json([
                'success'          => false,
                'message'          => $e->getMessage(),
                'compensation_log' => $e->getCompensationLog(),
            ], 422);
        });
    })
    ->create();
