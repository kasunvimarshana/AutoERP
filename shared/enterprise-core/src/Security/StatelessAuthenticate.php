<?php

namespace Enterprise\Core\Security;

use Closure;
use Enterprise\Core\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

/**
 * StatelessAuthenticate - Middleware for cross-service authentication.
 * Validates JWT, sets TenantContext, and ensures tenant isolation.
 */
class StatelessAuthenticate
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized: No token provided'], 401);
        }

        try {
            // In production, the public key should be fetched from a secure vault or Auth Service.
            $publicKey = config('enterprise.auth.public_key');
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // Set global tenant context from JWT claims
            $this->tenantContext->setContext(
                $decoded->tenant_id,
                $decoded->organization_id ?? null,
                $decoded->branch_id ?? null,
                $decoded->location_id ?? null,
                $decoded->department_id ?? null
            );

            // Bind the Authorization Service to the container
            $authService = new DefaultAuthorizationService($this->tenantContext, (array)$decoded);
            app()->instance(AuthorizationContract::class, $authService);

            // Store decoded user info in request for controller access
            $request->merge(['auth_user' => (array)$decoded]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error("JWT Validation Failed: " . $e->getMessage());
            return response()->json(['error' => 'Unauthorized: Invalid or expired token'], 401);
        }
    }
}
