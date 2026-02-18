<?php

namespace Modules\IAM\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        if (! $user->can($permission)) {
            throw new AuthorizationException("You do not have the required permission: {$permission}");
        }

        return $next($request);
    }
}
