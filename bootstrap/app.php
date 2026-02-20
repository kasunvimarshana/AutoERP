<?php

use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'locale' => SetLocale::class,
            'org.access' => EnsureOrganizationAccess::class,
            'idempotency' => IdempotencyMiddleware::class,
            'force.https' => ForceHttps::class,
        ]);
        $middleware->prepend(ForceHttps::class);
        $middleware->prependToGroup('api', HandleCors::class);
        $middleware->appendToGroup('api', SetLocale::class);
        $middleware->appendToGroup('api', IdempotencyMiddleware::class);
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
