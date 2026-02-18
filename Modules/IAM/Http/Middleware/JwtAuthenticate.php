<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\IAM\Services\AuthService;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Authentication Middleware
 * 
 * Validates JWT tokens and sets authenticated user and tenant context.
 * Provides stateless authentication for API requests.
 */
class JwtAuthenticate
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Token not provided.',
            ], 401);
        }
        
        // Validate token and get user
        $user = $this->authService->validateToken($token);
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated. Invalid or expired token.',
            ], 401);
        }
        
        // Set authenticated user
        $request->setUserResolver(fn() => $user);
        
        // Set tenant context from token
        $tenantId = $this->authService->getTenantFromToken($token);
        if ($tenantId) {
            Session::put('tenant_id', $tenantId);
            config(['app.current_tenant_id' => $tenantId]);
        }
        
        return $next($request);
    }
}
