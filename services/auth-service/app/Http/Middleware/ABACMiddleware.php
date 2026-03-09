<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ABACMiddleware
{
    /**
     * Attribute-Based Access Control middleware.
     *
     * Usage: ->middleware('abac:resource.action')
     *        ->middleware('abac:users.update,users.delete')
     *
     * Evaluates policies based on:
     * - Subject attributes (user roles, department, clearance level)
     * - Resource attributes (owner, classification, tenant_id)
     * - Environment attributes (time, IP, device)
     * - Action requested
     */
    public function handle(Request $request, Closure $next, string ...$policies): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error'   => 'UNAUTHENTICATED',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tenant    = $request->attributes->get('tenant');
        $subjectAttributes = $this->extractSubjectAttributes($user, $tenant);
        $envAttributes     = $this->extractEnvironmentAttributes($request);

        foreach ($policies as $policy) {
            [$resource, $action] = $this->parsePolicy($policy);

            $resourceAttributes = $this->extractResourceAttributes($request, $resource);

            if (!$this->evaluate($subjectAttributes, $resourceAttributes, $envAttributes, $action)) {
                return response()->json([
                    'success' => false,
                    'message' => "Access denied. Policy violation: {$policy}",
                    'error'   => 'ABAC_POLICY_VIOLATION',
                    'policy'  => $policy,
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }

    /**
     * Extract attributes describing the subject (user).
     */
    private function extractSubjectAttributes($user, $tenant): array
    {
        return [
            'id'         => $user->id,
            'email'      => $user->email,
            'roles'      => $user->getRoleNames()->toArray(),
            'permissions'=> $user->getAllPermissions()->pluck('name')->toArray(),
            'tenant_id'  => $user->tenant_id,
            'org_id'     => $user->org_id,
            'is_active'  => $user->is_active ?? true,
            'is_super'   => $user->hasRole('super-admin'),
        ];
    }

    /**
     * Extract environment attributes from the request.
     */
    private function extractEnvironmentAttributes(Request $request): array
    {
        return [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp'  => now()->timestamp,
            'hour'       => now()->hour,
            'device_id'  => $request->header('X-Device-ID'),
        ];
    }

    /**
     * Extract resource attributes based on resource type.
     */
    private function extractResourceAttributes(Request $request, string $resource): array
    {
        $routeModel = $request->route($resource);

        return [
            'type'      => $resource,
            'id'        => $routeModel?->id ?? $request->route('id'),
            'owner_id'  => $routeModel?->user_id ?? $routeModel?->created_by,
            'tenant_id' => $routeModel?->tenant_id,
        ];
    }

    /**
     * Parse policy string into resource + action.
     */
    private function parsePolicy(string $policy): array
    {
        $parts = explode('.', $policy, 2);

        return [$parts[0], $parts[1] ?? '*'];
    }

    /**
     * Core ABAC evaluation engine.
     */
    private function evaluate(array $subject, array $resource, array $env, string $action): bool
    {
        // Super-admin bypasses all ABAC checks
        if ($subject['is_super']) {
            return true;
        }

        // User must be active
        if (!$subject['is_active']) {
            return false;
        }

        // Cross-tenant access: deny if tenants don't match (unless super-admin)
        if (
            isset($resource['tenant_id']) &&
            $resource['tenant_id'] !== null &&
            $resource['tenant_id'] !== $subject['tenant_id']
        ) {
            return false;
        }

        // Resource owner can always perform actions on their own resources
        if (isset($resource['owner_id']) && $resource['owner_id'] === $subject['id']) {
            return true;
        }

        // Check if user has a direct permission matching resource.action
        $permissionKey = $resource['type'] . '.' . $action;
        if (in_array($permissionKey, $subject['permissions'], true)) {
            return true;
        }

        // Wildcard permission check
        $wildcardKey = $resource['type'] . '.*';
        if (in_array($wildcardKey, $subject['permissions'], true)) {
            return true;
        }

        // Role-based fallback: tenant-admin can do anything in their tenant
        if (in_array('tenant-admin', $subject['roles'], true)) {
            return true;
        }

        return false;
    }
}
