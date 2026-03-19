<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attribute-Based Access Control (ABAC) middleware.
 *
 * Evaluates attribute conditions beyond pure role checks:
 *  - Tenant active status
 *  - Account status (active / suspended)
 *  - Time-of-day / IP-based policies (extensible)
 *
 * Usage in routes:
 *   ->middleware('abac:require_active_tenant')
 *   ->middleware('abac:require_active_user')
 */
final class AbacAuthorization
{
    public function handle(Request $request, Closure $next, string ...$policies): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        foreach ($policies as $policy) {
            $result = $this->evaluatePolicy($policy, $request);

            if ($result !== null) {
                return $result;
            }
        }

        return $next($request);
    }

    private function evaluatePolicy(string $policy, Request $request): ?Response
    {
        $user = $request->user();

        return match ($policy) {
            'require_active_user' => $this->requireActiveUser($user),
            'require_active_tenant' => $this->requireActiveTenant($user),
            'require_verified_email' => $this->requireVerifiedEmail($user),
            default => null,
        };
    }

    private function requireActiveUser(?\App\Models\User $user): ?Response
    {
        if ($user === null || !$user->isActive()) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'ACCOUNT_INACTIVE',
                    'message' => 'Your account is not active.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function requireActiveTenant(?\App\Models\User $user): ?Response
    {
        if ($user === null || $user->tenant_id === null) {
            return null;
        }

        $tenant = $user->tenant;

        if ($tenant === null || $tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'TENANT_INACTIVE',
                    'message' => 'Your organisation is not active.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function requireVerifiedEmail(?\App\Models\User $user): ?Response
    {
        if ($user === null || $user->email_verified_at === null) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Email address must be verified.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }
}
