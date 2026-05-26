<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\UseCases\ValidateTokenServiceInterface;
use Symfony\Component\HttpFoundation\Response;

final class TokenValidationMiddleware
{
    public function __construct(private readonly ValidateTokenServiceInterface $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $bearerToken = $request->bearerToken();
        if (! is_string($bearerToken) || trim($bearerToken) === '') {
            return new JsonResponse([
                'message' => 'Bearer token is required.',
                'code' => 'AUTH_UNAUTHORIZED_ACCESS',
            ], 401);
        }

        $tenantId = $request->attributes->get('current_tenant_id');
        $result = $this->tokens->validateToken(
            $bearerToken,
            is_numeric($tenantId) ? (int) $tenantId : null,
        );

        if ($result->isFailure()) {
            return new JsonResponse([
                'message' => $result->errorOrFail()->message,
                'code' => $result->errorOrFail()->code,
            ], 401);
        }

        $request->attributes->set('auth_access_token', $result->valueOrFail());

        return $next($request);
    }
}
