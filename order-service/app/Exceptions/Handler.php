<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): JsonResponse
    {
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($e instanceof HttpException) {
            return response()->json(['message' => $e->getMessage() ?: 'HTTP error'], $e->getStatusCode());
        }

        $statusCode = 500;
        $message    = config('app.debug')
            ? $e->getMessage()
            : 'An internal server error occurred';

        return response()->json(['message' => $message], $statusCode);
    }
}
