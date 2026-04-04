<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Tenant resolution logic will be implemented here.
        return $next($request);
    }
}
