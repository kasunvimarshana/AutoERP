<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // API error responses
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with consistent JSON format
     */
    protected function handleApiException($request, Throwable $exception)
    {
        $statusCode = 500;
        $message = 'Internal Server Error';
        $errors = null;

        // Authentication Exception
        if ($exception instanceof AuthenticationException) {
            $statusCode = 401;
            $message = 'Unauthenticated';
        }
        // Authorization Exception
        elseif ($exception instanceof AuthorizationException) {
            $statusCode = 403;
            $message = $exception->getMessage() ?: 'Forbidden';
        }
        // Validation Exception
        elseif ($exception instanceof ValidationException) {
            $statusCode = 422;
            $message = 'Validation failed';
            $errors = $exception->errors();
        }
        // Model Not Found
        elseif ($exception instanceof ModelNotFoundException) {
            $statusCode = 404;
            $message = 'Resource not found';
        }
        // Not Found HTTP Exception
        elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $message = 'Endpoint not found';
        }
        // Method Not Allowed
        elseif ($exception instanceof MethodNotAllowedHttpException) {
            $statusCode = 405;
            $message = 'Method not allowed';
        }
        // Rate Limiting
        elseif ($exception instanceof TooManyRequestsHttpException) {
            $statusCode = 429;
            $message = 'Too many requests';
        }
        // Custom Business Exceptions
        elseif ($exception instanceof BusinessException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
            $errors = $exception->getErrors();
        }
        // Generic HTTP Exceptions
        elseif (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An error occurred';
        }
        // Unknown Exceptions (500)
        else {
            $message = config('app.debug') 
                ? $exception->getMessage() 
                : 'Internal Server Error';
        }

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        // Add debug info in development
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }
}
