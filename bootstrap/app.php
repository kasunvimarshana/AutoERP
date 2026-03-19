<?php

declare(strict_types=1);

use App\Http\Middleware\SuspiciousActivityDetection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'suspicious' => SuspiciousActivityDetection::class,
            'token.version' => \App\Http\Middleware\VerifyTokenVersion::class,
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'abac' => \App\Http\Middleware\AbacAuthorization::class,
        ]);
    })
    ->withProviders([
        App\Providers\RepositoryServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\AuthException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'AUTH_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], $e->getCode() ?: 401);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'UNAUTHENTICATED',
                    'message' => 'Unauthenticated.',
                ],
            ], 401);
        });
    })->create();
