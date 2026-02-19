<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserDevice;
use Modules\Tenant\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * JwtAuthMiddleware
 *
 * Handles JWT authentication for stateless requests
 */
class JwtAuthMiddleware
{
    public function __construct(
        protected TokenServiceInterface $tokenService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $this->tokenService->validate($token);

        if (! $payload) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Set the authenticated user
        $user = User::find($payload['sub']);

        if (! $user || ! $user->is_active) {
            return response()->json(['error' => 'User not found or inactive'], 401);
        }

        // Set tenant and organization context
        if (isset($payload['tenant_id'])) {
            $this->tenantContext->setTenant($payload['tenant_id']);
        }

        if (isset($payload['organization_id'])) {
            $this->tenantContext->setOrganization($payload['organization_id']);
        }

        // Update device last used
        if (isset($payload['device_id'])) {
            UserDevice::where('device_id', $payload['device_id'])
                ->where('user_id', $user->id)
                ->first()
                ?->markAsUsed();
        }

        // Set authenticated user in request
        $request->setUserResolver(fn () => $user);

        // Add token payload to request
        $request->merge(['token_payload' => $payload]);

        return $next($request);
    }

    /**
     * Extract token from request
     */
    protected function extractToken(Request $request): ?string
    {
        // Try from Authorization header (Bearer token)
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // Try from query parameter (for websockets, etc.)
        return $request->query('token');
    }
}
