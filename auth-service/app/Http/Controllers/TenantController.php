<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\TenantServiceInterface;
use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages tenant CRUD operations.
 *
 * Super-admin only endpoints; tenant isolation is enforced
 * at the middleware level before reaching this controller.
 */
final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService,
    ) {}

    /**
     * GET /api/v1/tenants
     *
     * List all tenants (super-admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', '15');
        $tenants = $this->tenantService->listTenants($perPage);

        return response()->json([
            'message' => 'Tenants retrieved.',
            'data'    => $tenants,
        ]);
    }

    /**
     * POST /api/v1/tenants
     *
     * Create a new tenant.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->createTenant($request->validated());

        return response()->json([
            'message' => 'Tenant created successfully.',
            'data'    => $tenant,
        ], 201);
    }

    /**
     * GET /api/v1/tenants/{tenant}
     *
     * Show a specific tenant.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant retrieved.',
            'data'    => $tenant->load('users'),
        ]);
    }

    /**
     * PUT /api/v1/tenants/{tenant}
     *
     * Update tenant metadata.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $updated = $this->tenantService->updateTenant($tenant, $request->validated());

        return response()->json([
            'message' => 'Tenant updated successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * DELETE /api/v1/tenants/{tenant}
     *
     * Deactivate (soft-delete) a tenant.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->deactivateTenant($tenant);

        return response()->json(['message' => 'Tenant deactivated successfully.']);
    }
}
