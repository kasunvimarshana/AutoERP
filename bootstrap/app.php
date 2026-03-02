<?php

declare(strict_types=1);

use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request): Response {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data'    => null,
                    'errors'  => null,
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request): Response {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'data'    => null,
                'errors'  => null,
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request): Response {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'data'    => null,
                    'errors'  => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request): Response {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'data'    => null,
                'errors'  => null,
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request): Response {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found.',
                'data'    => null,
                'errors'  => null,
            ], Response::HTTP_NOT_FOUND);
        });
    })->create();
