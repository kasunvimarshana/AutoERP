<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Global exception handler — returns structured JSON error responses for the API.
 */
class Handler extends ExceptionHandler
{
    /**
     * Exceptions that should not be reported.
     */
    protected $dontReport = [
        \App\Exceptions\AuthenticationException::class,
    ];

    /**
     * Exceptions that should not be flashed for redirect responses.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    private function handleApiException(Throwable $e, $request): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'error'   => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException
            || $e instanceof \App\Exceptions\AuthenticationException) {
            return response()->json([
                'error' => 'Unauthenticated.',
            ], 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'error' => 'This action is unauthorized.',
            ], 403);
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'error' => "{$model} not found.",
            ], 404);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'error' => $e->getMessage() ?: 'HTTP error.',
            ], $e->getStatusCode());
        }

        if ($e instanceof \DomainException) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

        if ($e instanceof \UnderflowException) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

        // Unhandled / unexpected errors.
        $statusCode = 500;
        $message    = config('app.debug') ? $e->getMessage() : 'Internal server error.';

        return response()->json([
            'error' => $message,
        ], $statusCode);
    }
}
