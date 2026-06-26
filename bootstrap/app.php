<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\Authorize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Core\Exceptions\DomainException;
use Modules\Core\Http\Middleware\CurrentOrganizationUnitMiddleware;
use Modules\Core\Http\Middleware\CurrentTenantMiddleware;
use Modules\Core\Http\Middleware\CurrentUserMiddleware;
use Modules\Core\Http\Middleware\EnsureApiErrorResponseMiddleware;
use Modules\Core\Http\Middleware\RequestCorrelationIdMiddleware;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) env('TRUSTED_PROXIES', '')),
        )));
        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $middleware->alias([
            (string) env('CORE_CURRENT_USER_MIDDLEWARE_ALIAS', 'current.user') => CurrentUserMiddleware::class,
            (string) env('CORE_CURRENT_TENANT_MIDDLEWARE_ALIAS', 'current.tenant') => CurrentTenantMiddleware::class,
            (string) env('CORE_CURRENT_ORGANIZATION_UNIT_MIDDLEWARE_ALIAS', 'current.organization-unit') => CurrentOrganizationUnitMiddleware::class,
        ]);

        // Route model binding must never query tenant-owned models before the
        // authenticated tenant execution context has been established.
        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            AuthenticatesRequests::class,
            CurrentUserMiddleware::class,
            CurrentTenantMiddleware::class,
            CurrentOrganizationUnitMiddleware::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);

        $middleware->append(RequestCorrelationIdMiddleware::class);
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

        $exceptions->render(function (DomainException $exception, Request $request) use ($errorResponse) {
            return $request->is('api/*') || $request->expectsJson()
                ? $errorResponse(422, $exception->getMessage())
                : null;
        });

        $exceptions->render(function (InvalidArgumentException $exception, Request $request) use ($errorResponse) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            report($exception);

            return $errorResponse(422, 'The request contains an invalid value.');
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

            $correlationId = $request->attributes->get(RequestCorrelationIdMiddleware::ATTRIBUTE);
            $context = [
                'correlation_id' => is_string($correlationId) ? $correlationId : null,
                'method' => $request->method(),
                'path' => $request->path(),
                'exception' => $exception,
            ];

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = $status >= 500
                    ? 'Unexpected server error.'
                    : ($exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error.');
                if ($status >= 500) {
                    logger()->error('Unhandled API HTTP exception.', $context);
                }

                return $errorResponse($status, $message);
            }

            logger()->error('Unhandled API exception.', $context);

            return $errorResponse(500, 'Unexpected server error.');
        });
    })->create();
