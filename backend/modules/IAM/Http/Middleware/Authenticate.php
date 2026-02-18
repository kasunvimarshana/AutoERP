<?php

namespace Modules\IAM\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! $request->user()->is_active) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        return $next($request);
    }
}
