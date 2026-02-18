<?php

namespace Modules\IAM\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        if (! $user->hasAnyRole($roles)) {
            $rolesList = implode(', ', $roles);
            throw new AuthorizationException("You must have one of the following roles: {$rolesList}");
        }

        return $next($request);
    }
}
