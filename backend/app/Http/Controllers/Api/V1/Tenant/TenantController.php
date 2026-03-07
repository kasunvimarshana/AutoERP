<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Application\DTOs\TenantDTO;
use App\Application\Services\Tenant\TenantService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenant management controller.
 */
final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    /** GET /api/v1/tenants */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Models\Tenant::class);

        return TenantResource::collection($this->tenantService->list($request->query()));
    }

    /** POST /api/v1/tenants */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Tenant::class);

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'slug'     => ['required', 'string', 'max:100', 'unique:tenants,slug', 'regex:/^[a-z0-9\-]+$/'],
            'plan'     => ['sometimes', 'string', 'in:starter,growth,enterprise'],
            'domain'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_users'=> ['sometimes', 'integer', 'min:1'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $tenant = $this->tenantService->create(TenantDTO::fromRequest($request));

        return TenantResource::make($tenant)->response()->setStatusCode(201);
    }

    /** GET /api/v1/tenants/{id} */
    public function show(int $id): TenantResource
    {
        $this->authorize('view', \App\Models\Tenant::class);

        return TenantResource::make($this->tenantService->get($id));
    }

    /** PUT /api/v1/tenants/{id} */
    public function update(Request $request, int $id): TenantResource
    {
        $this->authorize('update', \App\Models\Tenant::class);

        $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'status'   => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'plan'     => ['sometimes', 'string', 'in:starter,growth,enterprise'],
            'max_users'=> ['sometimes', 'integer', 'min:1'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ]);

        return TenantResource::make($this->tenantService->update($id, $request->validated()));
    }

    /** DELETE /api/v1/tenants/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', \App\Models\Tenant::class);

        $this->tenantService->delete($id);

        return response()->json(['message' => 'Tenant deleted.']);
    }

    /** PATCH /api/v1/tenants/{id}/config */
    public function updateConfig(Request $request, int $id): TenantResource
    {
        $this->authorize('update', \App\Models\Tenant::class);

        $request->validate([
            'config' => ['required', 'array'],
        ]);

        return TenantResource::make($this->tenantService->updateConfig($id, $request->input('config')));
    }
}
