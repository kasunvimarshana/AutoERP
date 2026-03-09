<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [],

        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth'           => \App\Http\Middleware\AuthenticateMiddleware::class,
        'tenant'         => \App\Http\Middleware\TenantMiddleware::class,
        'throttle'       => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'bindings'       => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can'            => \Illuminate\Auth\Middleware\Authorize::class,
        'signed'         => \Illuminate\Routing\Middleware\ValidateSignature::class,
    ];
}
