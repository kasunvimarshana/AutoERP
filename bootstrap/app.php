<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Middleware\CurrentOrganizationUnitMiddleware;
use Modules\Core\Http\Middleware\CurrentTenantMiddleware;
use Modules\Core\Http\Middleware\CurrentUserMiddleware;
use Modules\Core\Http\Middleware\NormalizeApiErrorResponseMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

if (! function_exists('api_error_response')) {
    function api_error_response(
        string $code,
        string $message,
        int $status,
        string $type,
        array $details = [],
    ) {
        $payload = [
            'message' => $message,
            'error' => [
                'code' => $code,
                'type' => $type,
                'message' => $message,
                'details' => (object) $details,
            ],
        ];

        if (isset($details['fields']) && is_array($details['fields'])) {
            $payload['errors'] = $details['fields'];
        }

        return response()->json($payload, $status);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            (string) env('CORE_CURRENT_USER_MIDDLEWARE_ALIAS', 'current.user') => CurrentUserMiddleware::class,
            (string) env('CORE_CURRENT_TENANT_MIDDLEWARE_ALIAS', 'current.tenant') => CurrentTenantMiddleware::class,
            (string) env('CORE_CURRENT_ORGANIZATION_UNIT_MIDDLEWARE_ALIAS', 'current.organization-unit') => CurrentOrganizationUnitMiddleware::class,
        ]);

        $middleware->append(NormalizeApiErrorResponseMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error_response('AUTHENTICATION_FAILED', 'Unauthenticated.', 401, 'authentication');
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error_response('AUTHORIZATION_DENIED', 'This action is not authorized.', 403, 'authorization');
            }

            return null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error_response(
                    'VALIDATION_FAILED',
                    'The given data was invalid.',
                    422,
                    'validation',
                    ['fields' => $exception->errors()],
                );
            }

            return null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error_response('RESOURCE_NOT_FOUND', 'Resource not found.', 404, 'not_found');
            }

            return null;
        });

        $exceptions->render(function (HttpResponseException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $exception->getResponse();
            }

            return null;
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();

                return api_error_response(
                    'HTTP_'.(string) $status,
                    $exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error.',
                    $status,
                    $status === 404 ? 'not_found' : 'http',
                );
            }

            report($exception);

            return api_error_response(
                'UNEXPECTED_ERROR',
                'Unexpected server error.',
                500,
                'infrastructure',
            );
        });
    })->create();
