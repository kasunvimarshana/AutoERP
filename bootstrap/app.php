<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            (string) env('CORE_CURRENT_USER_MIDDLEWARE_ALIAS', 'current.user')
                => \Modules\Core\Presentation\Http\Middleware\CurrentUserMiddleware::class,
            (string) env('CORE_CURRENT_TENANT_MIDDLEWARE_ALIAS', 'current.tenant')
                => \Modules\Core\Presentation\Http\Middleware\CurrentTenantMiddleware::class,
            (string) env('CORE_CURRENT_ORGANIZATION_UNIT_MIDDLEWARE_ALIAS', 'current.organization-unit')
                => \Modules\Core\Presentation\Http\Middleware\CurrentOrganizationUnitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
