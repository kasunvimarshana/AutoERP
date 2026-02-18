<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Organization\Entities\Organization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Organization Context Middleware
 *
 * Validates and initializes organization context for API requests.
 * Works in conjunction with TenantContext to provide multi-organization support.
 *
 * This middleware:
 * - Identifies organization from header, parameter, or authenticated user
 * - Validates organization belongs to current tenant
 * - Stores organization and location context in session
 * - Returns 403 if organization not found, inactive, or doesn't belong to tenant
 *
 * Resolution Order:
 * 1. X-Organization-ID header (explicit org selection)
 * 2. Query parameter 'organization_id'
 * 3. Authenticated user's default organization
 * 4. Tenant's default/root organization
 *
 * Usage: Apply to organization-specific routes
 * Route::middleware(['tenant.context', 'organization.context'])->group(...)
 */
class OrganizationContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure tenant context is set
        $tenantId = Session::get('tenant_id') ?? $request->input('tenant_id');
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant context required before organization context',
            ], Response::HTTP_FORBIDDEN);
        }

        // Resolve organization ID
        $organizationId = $this->resolveOrganizationId($request);

        if (!$organizationId) {
            return response()->json([
                'message' => 'Organization identification required',
            ], Response::HTTP_FORBIDDEN);
        }

        // Validate organization exists, is active, and belongs to tenant
        $organization = $this->getOrganization($organizationId, $tenantId);

        if (!$organization) {
            return response()->json([
                'message' => 'Invalid organization or organization does not belong to tenant',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isOrganizationActive($organization)) {
            return response()->json([
                'message' => 'Organization is inactive',
            ], Response::HTTP_FORBIDDEN);
        }

        // Store organization context in session
        Session::put('organization_id', $organization->id);
        Session::put('organization', $organization);
        $request->merge(['organization_id' => $organization->id]);

        // Optionally resolve and set location context
        $locationId = $this->resolveLocationId($request, $organization);
        if ($locationId) {
            Session::put('location_id', $locationId);
            $request->merge(['location_id' => $locationId]);
        }

        return $next($request);
    }

    /**
     * Resolve organization ID from request.
     */
    protected function resolveOrganizationId(Request $request): ?int
    {
        // Try from header first (API requests)
        if ($request->header('X-Organization-ID')) {
            return (int) $request->header('X-Organization-ID');
        }

        // Try from query parameter
        if ($request->query('organization_id')) {
            return (int) $request->query('organization_id');
        }

        // Try from authenticated user's default organization
        if (auth()->check() && method_exists(auth()->user(), 'getDefaultOrganizationId')) {
            return auth()->user()->getDefaultOrganizationId();
        }

        // Try from user's organization_id
        if (auth()->check() && isset(auth()->user()->organization_id)) {
            return auth()->user()->organization_id;
        }

        // Fallback: get tenant's default/root organization
        $tenantId = Session::get('tenant_id');
        if ($tenantId) {
            return $this->getTenantDefaultOrganization($tenantId);
        }

        return null;
    }

    /**
     * Resolve location ID from request or organization default.
     */
    protected function resolveLocationId(Request $request, Organization $organization): ?int
    {
        // Try from header
        if ($request->header('X-Location-ID')) {
            $locationId = (int) $request->header('X-Location-ID');
            // Validate location belongs to organization
            if ($this->locationBelongsToOrganization($locationId, $organization->id)) {
                return $locationId;
            }
        }

        // Try from query parameter
        if ($request->query('location_id')) {
            $locationId = (int) $request->query('location_id');
            if ($this->locationBelongsToOrganization($locationId, $organization->id)) {
                return $locationId;
            }
        }

        // Try from authenticated user
        if (auth()->check() && isset(auth()->user()->location_id)) {
            $locationId = auth()->user()->location_id;
            if ($this->locationBelongsToOrganization($locationId, $organization->id)) {
                return $locationId;
            }
        }

        return null;
    }

    /**
     * Get organization by ID and validate tenant ownership.
     */
    protected function getOrganization(int $organizationId, int $tenantId): ?Organization
    {
        return Organization::where('id', $organizationId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Check if organization is active.
     */
    protected function isOrganizationActive(Organization $organization): bool
    {
        return $organization->status === 'active';
    }

    /**
     * Get tenant's default organization (root organization).
     */
    protected function getTenantDefaultOrganization(int $tenantId): ?int
    {
        $organization = Organization::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->first();

        return $organization?->id;
    }

    /**
     * Validate location belongs to organization.
     */
    protected function locationBelongsToOrganization(int $locationId, int $organizationId): bool
    {
        return \DB::table('locations')
            ->where('id', $locationId)
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
