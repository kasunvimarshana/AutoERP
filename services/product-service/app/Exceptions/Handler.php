<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    private function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? 'HTTP Error',
            ], $e->getStatusCode());
        }

        // Generic 500 — never leak internals in production
        $message = config('app.debug')
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
