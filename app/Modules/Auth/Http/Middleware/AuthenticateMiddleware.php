<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return new JsonResponse([
                'message' => 'Authentication is required.',
                'code' => 'AUTH_UNAUTHORIZED_ACCESS',
            ], 401);
        }

        return $next($request);
    }
}
