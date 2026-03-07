<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends BaseController
{
    public function __construct(private readonly TenantConfigService $tenantConfigService) {}

    // -------------------------------------------------------------------------
    // GET /api/tenants
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('domain', 'LIKE', "%{$search}%");
        }

        if ($request->has('per_page')) {
            $tenants = $query->paginate((int) $request->input('per_page', 15));

            return $this->paginatedResponse($tenants, 'Tenants retrieved');
        }

        return $this->successResponse($query->get(), 'Tenants retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/tenants
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'plan'   => ['nullable', 'string', 'in:free,starter,professional,enterprise'],
            'config' => ['nullable', 'array'],
        ]);

        $tenant = Tenant::create([
            'name'      => $data['name'],
            'domain'    => $data['domain'],
            'plan'      => $data['plan'] ?? 'free',
            'is_active' => true,
            'config'    => $data['config'] ?? [],
        ]);

        return $this->createdResponse($tenant, 'Tenant created');
    }

    // -------------------------------------------------------------------------
    // GET /api/tenants/{id}
    // -------------------------------------------------------------------------

    public function show(int|string $id): JsonResponse
    {
        $tenant = Tenant::with('users')->find($id);

        if (! $tenant) {
            return $this->notFoundResponse('Tenant not found');
        }

        return $this->successResponse($tenant, 'Tenant retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT/PATCH /api/tenants/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, int|string $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (! $tenant) {
            return $this->notFoundResponse('Tenant not found');
        }

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'domain'    => ['sometimes', 'string', 'max:255', 'unique:tenants,domain,' . $id],
            'plan'      => ['nullable', 'string', 'in:free,starter,professional,enterprise'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $tenant->fill($data)->save();

        // Purge tenant config cache after update
        $this->tenantConfigService->refreshCache($id);

        return $this->successResponse($tenant, 'Tenant updated');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/tenants/{id}
    // -------------------------------------------------------------------------

    public function destroy(int|string $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (! $tenant) {
            return $this->notFoundResponse('Tenant not found');
        }

        $tenant->users()->delete();
        $tenant->delete();
        $this->tenantConfigService->refreshCache($id);

        return $this->successResponse(null, 'Tenant deleted');
    }

    // -------------------------------------------------------------------------
    // GET /api/tenants/{id}/config
    // -------------------------------------------------------------------------

    public function getConfig(int|string $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (! $tenant) {
            return $this->notFoundResponse('Tenant not found');
        }

        try {
            $config = $this->tenantConfigService->getConfig($id);

            return $this->successResponse($config, 'Tenant config retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve config', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // PATCH /api/tenants/{id}/config
    // -------------------------------------------------------------------------

    public function updateConfig(Request $request, int|string $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (! $tenant) {
            return $this->notFoundResponse('Tenant not found');
        }

        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        try {
            $this->tenantConfigService->updateConfig($id, $data['config']);

            return $this->successResponse(
                $this->tenantConfigService->getConfig($id),
                'Tenant config updated'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update config', $e->getMessage(), 500);
        }
    }
}
