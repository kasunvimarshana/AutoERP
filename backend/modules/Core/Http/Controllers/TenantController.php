<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\TenantResource;
use Modules\Core\Models\Tenant;
use Modules\Core\Services\TenantService;

class TenantController extends BaseController
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * @OA\Get(
     *     path="/api/tenants",
     *     operationId="getTenantsList",
     *     tags={"Tenants"},
     *     summary="Get list of all tenants",
     *     description="Retrieve a paginated list of tenants with optional filtering by status and search query. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by tenant status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "suspended", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter by tenant name or domain",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/TenantResource")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('domain', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->success(TenantResource::collection($tenants));
    }

    /**
     * @OA\Post(
     *     path="/api/tenants",
     *     operationId="createTenant",
     *     tags={"Tenants"},
     *     summary="Create a new tenant",
     *     description="Create a new tenant organization with specified details. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "domain"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Acme Corporation", description="Tenant organization name"),
     *             @OA\Property(property="domain", type="string", maxLength=255, example="acme.example.com", description="Unique domain for the tenant"),
     *             @OA\Property(property="plan", type="string", maxLength=50, example="enterprise", description="Subscription plan"),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 description="Optional tenant-specific settings",
     *                 example={"theme": {"primary": "#3B82F6"}, "timezone": "UTC"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tenant created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tenant created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/TenantResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"domain": {"The domain has already been taken."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:tenants,domain|max:255',
            'plan' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        try {
            $tenant = $this->tenantService->createTenant($validated);

            return $this->created(TenantResource::make($tenant), 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tenants/{uuid}",
     *     operationId="getTenantById",
     *     tags={"Tenants"},
     *     summary="Get tenant by UUID",
     *     description="Retrieve detailed information about a specific tenant by its UUID. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Tenant UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", ref="#/components/schemas/TenantResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(
     *         response=404,
     *         description="Tenant not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $uuid)
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);

        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }

        return $this->success(TenantResource::make($tenant));
    }

    /**
     * @OA\Put(
     *     path="/api/tenants/{uuid}",
     *     operationId="updateTenant",
     *     tags={"Tenants"},
     *     summary="Update tenant details",
     *     description="Update an existing tenant's information. All fields are optional. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Tenant UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Acme Corporation Updated"),
     *             @OA\Property(property="domain", type="string", maxLength=255, example="acme-new.example.com"),
     *             @OA\Property(property="plan", type="string", maxLength=50, example="professional"),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 description="Tenant-specific settings",
     *                 example={"theme": {"primary": "#10B981"}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resource updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/TenantResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Tenant not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(Request $request, string $uuid)
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);

        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255|unique:tenants,domain,'.$tenant->id,
            'plan' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        try {
            $tenant = $this->tenantService->updateTenant($tenant, $validated);

            return $this->updated(TenantResource::make($tenant));
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tenants/{uuid}",
     *     operationId="deleteTenant",
     *     tags={"Tenants"},
     *     summary="Delete a tenant",
     *     description="Permanently delete a tenant and all associated data. This action cannot be undone. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Tenant UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resource deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Tenant not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(string $uuid)
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);

        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }

        try {
            $this->tenantService->deleteTenant($tenant);

            return $this->deleted();
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tenants/{uuid}/suspend",
     *     operationId="suspendTenant",
     *     tags={"Tenants"},
     *     summary="Suspend a tenant",
     *     description="Suspend a tenant's access to the system. The tenant will not be able to login or access resources. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Tenant UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant suspended successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tenant suspended successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Tenant not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function suspend(string $uuid)
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);

        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }

        $tenant = $this->tenantService->suspendTenant($tenant);

        return $this->success($tenant, 'Tenant suspended successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/tenants/{uuid}/activate",
     *     operationId="activateTenant",
     *     tags={"Tenants"},
     *     summary="Activate a tenant",
     *     description="Activate a previously suspended tenant, restoring full access to the system. Requires admin role.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Tenant UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant activated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tenant activated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Admin role required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Tenant not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function activate(string $uuid)
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);

        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }

        $tenant = $this->tenantService->activateTenant($tenant);

        return $this->success($tenant, 'Tenant activated successfully');
    }
}
