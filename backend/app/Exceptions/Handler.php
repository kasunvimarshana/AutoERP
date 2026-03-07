<?php

namespace App\Exceptions;

use App\Exceptions\AuthorizationException as AppAuthorizationException;
use App\Exceptions\SagaException;
use App\Exceptions\TenantException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic (e.g. Sentry, Datadog) goes here
        });
    }

    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->is('v1/*')) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function renderApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
            ], 401);
        }

        if ($e instanceof AppAuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        if ($e instanceof TenantException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }

        if ($e instanceof SagaException) {
            return response()->json([
                'success'          => false,
                'message'          => $e->getMessage(),
                'compensation_log' => $e->getCompensationLog(),
            ], 422);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'HTTP error',
            ], $e->getStatusCode());
        }

        // Generic 500
        $payload = [
            'success' => false,
            'message' => 'An unexpected error occurred.',
        ];

        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ];
        }

        return response()->json($payload, 500);
    }
}
