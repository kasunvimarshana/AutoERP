<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\AuthorizationException;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Exceptions\DomainException;
use Modules\Core\Exceptions\NotFoundException;
use Modules\Core\Exceptions\ValidationException;
use Throwable;

/**
 * Application Exception Handler
 *
 * Handles exceptions globally for the application
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthorizationException::class,
        NotFoundException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return $this->renderDomainException($e);
            }
        });
    }

    /**
     * Render a domain exception as JSON response
     */
    protected function renderDomainException(DomainException $exception): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $exception->getErrorCode(),
                'message' => $exception->getMessage(),
            ],
        ];

        // Add validation errors if available
        if ($exception instanceof ValidationException) {
            $response['error']['errors'] = $exception->getErrors();
        }

        // Add business rule name if available
        if ($exception instanceof BusinessRuleException && $exception->getRuleName()) {
            $response['error']['rule'] = $exception->getRuleName();
        }

        // Add context data if available and not empty
        if (! empty($exception->getContext())) {
            $response['error']['context'] = $exception->getContext();
        }

        return response()->json($response, $exception->getHttpStatusCode());
    }
}
