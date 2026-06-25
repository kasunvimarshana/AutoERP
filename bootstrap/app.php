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
use Modules\Core\Exceptions\DomainException;
use Modules\Core\Http\Middleware\CurrentOrganizationUnitMiddleware;
use Modules\Core\Http\Middleware\CurrentTenantMiddleware;
use Modules\Core\Http\Middleware\CurrentUserMiddleware;
use Modules\Core\Http\Middleware\EnsureApiErrorResponseMiddleware;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        $middleware->append(EnsureApiErrorResponseMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $errorResponse = static function (int $status, string $message, array $details = []) {
            return app(ApiErrorResponseFactory::class)->forStatus($status, $message, $details);
        };

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(401, 'Unauthenticated.')
                : null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(403, 'This action is not authorized.')
                : null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(422, 'Validation failed.', ['fields' => $exception->errors()])
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(404, 'Resource not found.')
                : null;
        });

        $exceptions->render(function (DomainException|InvalidArgumentException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(422, $exception->getMessage())
                : null;
        });

        $exceptions->render(function (HttpResponseException $exception, Request $request) {
            return $request->is('api/*') || $request->expectsJson()
                ? $exception->getResponse()
                : null;
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($errorResponse) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = $status >= 500
                    ? 'Unexpected server error.'
                    : ($exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error.');

                return $errorResponse($status, $message);
            }

            report($exception);

            return $errorResponse(500, 'Unexpected server error.');
        });
    })->create();
