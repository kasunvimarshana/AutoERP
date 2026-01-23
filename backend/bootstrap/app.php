<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load module routes
            $moduleRoutes = [
                'customer-management',
                'appointment-management',
                'job-card-management',
                'inventory-management',
                'invoicing-management',
            ];
            
            foreach ($moduleRoutes as $module) {
                $routePath = __DIR__.'/../routes/modules/'.$module.'.php';
                if (file_exists($routePath)) {
                    require $routePath;
                }
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
