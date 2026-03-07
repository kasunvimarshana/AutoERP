<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Integrate with external error trackers (e.g. Sentry) here
        });
    }

    public function render($request, Throwable $e): JsonResponse
    {
        // Validation errors → 422
        if ($e instanceof ValidationException) {
            return response()->json([
                'error'   => 'Validation failed',
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Model not found → 404
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $model   = $e instanceof ModelNotFoundException ? class_basename($e->getModel()) : 'Resource';
            return response()->json([
                'error'   => 'Not found',
                'message' => "{$model} not found.",
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Authorization → 403
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'error'   => 'Forbidden',
                'message' => $e->getMessage() ?: 'You are not authorized to perform this action.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // Domain / Business logic errors
        if ($e instanceof \DomainException) {
            return response()->json([
                'error'   => 'Business rule violation',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Generic HTTP exceptions
        if ($e instanceof HttpException) {
            return response()->json([
                'error'   => 'HTTP error',
                'message' => $e->getMessage() ?: 'An HTTP error occurred.',
            ], $e->getStatusCode());
        }

        // Runtime / unexpected errors (hide details in production)
        $debug   = config('app.debug', false);
        $message = $debug ? $e->getMessage() : 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'error'   => 'Internal server error',
            'message' => $message,
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
