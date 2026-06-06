<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\UseCases\ValidateTokenService;
use Symfony\Component\HttpFoundation\Response;

final class TokenValidationMiddleware
{
    public function __construct(private readonly ValidateTokenService $tokens) {}

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

        $tenantId = $this->resolveTenantId($request);
        $result = $this->tokens->validateToken(
            $bearerToken,
            $tenantId,
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

    private function resolveTenantId(Request $request): ?int
    {
        $candidates = [
            $request->attributes->get('current_tenant_id'),
            $request->input('tenant_id'),
            $request->headers->get('X-Tenant-ID'),
            $request->headers->get('X-Tenant-Id'),
            $request->headers->get('X-Tenant'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }
}
