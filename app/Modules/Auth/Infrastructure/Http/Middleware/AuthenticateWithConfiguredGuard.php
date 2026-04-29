<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory as Auth;

class AuthenticateWithConfiguredGuard extends Authenticate
{
    public function __construct(Auth $auth)
    {
        parent::__construct($auth);
    }

    public function handle($request, Closure $next, ...$guards)
    {
        if (empty($guards)) {
            $guards = [(string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))];
        }

        return parent::handle($request, $next, ...$guards);
    }
}
