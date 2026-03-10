<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auth\Services\TenantService;
use App\Http\Requests\Tenant\UpdateTenantConfigRequest;
use App\Infrastructure\MultiTenant\TenantManager;
use App\Infrastructure\MultiTenant\TenantRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * TenantController
 *
 * Management API for tenants.  Supports runtime config updates that take
 * effect immediately without service restart.
 */
class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly TenantManager    $tenantManager,
    ) {}

    // GET /api/tenants
    public function index(Request $request): JsonResponse
    {
        $perPage  = (int) $request->get('per_page', 15);
        $filters  = $request->only(['is_active', 'plan', 'name:like']);

        $tenants = $this->tenantRepository->paginate($filters, $perPage);

        return response()->json($tenants);
    }

    // GET /api/tenants/{id}
    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        return response()->json(['data' => $tenant]);
    }

    // POST /api/tenants
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'slug'     => ['required', 'string', 'max:100', 'unique:tenants,slug', 'alpha_dash'],
            'domain'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'plan'     => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'config'   => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $tenant = $this->tenantRepository->create($data);

        return response()->json(['data' => $tenant], 201);
    }

    // PUT /api/tenants/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'domain'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'plan'      => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata'  => ['sometimes', 'array'],
        ]);

        $tenant = $this->tenantRepository->update($id, $data);

        return response()->json(['data' => $tenant]);
    }

    // DELETE /api/tenants/{id}
    public function destroy(string $id): JsonResponse
    {
        $this->tenantRepository->softDelete($id);
        return response()->json(['message' => 'Tenant deleted successfully.']);
    }

    /**
     * PATCH /api/tenants/{id}/config
     *
     * Updates tenant runtime configuration at runtime — takes effect
     * on the next request without restarting the service.
     */
    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        $this->tenantManager->setTenantConfig($id, $data['config']);

        return response()->json([
            'message' => 'Tenant configuration updated. Changes take effect on next request.',
        ]);
    }
}
