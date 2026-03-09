<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\TenantServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Resources\Tenant\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->list($request->all());

        return TenantResource::collection($tenants);
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully.',
            'data'    => new TenantResource($tenant),
        ], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantService->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new TenantResource($tenant),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'plan'     => ['sometimes', 'string', 'in:free,starter,pro,enterprise'],
            'status'   => ['sometimes', 'string', 'in:active,suspended,cancelled'],
            'features' => ['sometimes', 'array'],
            'settings' => ['sometimes', 'array'],
            'config'   => ['sometimes', 'array'],
        ]);

        $tenant = $this->tenantService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data'    => new TenantResource($tenant),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->tenantService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully.',
        ]);
    }

    public function switchTenant(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $result = $this->tenantService->switchTenant(
            user: $request->user(),
            targetTenantId: $id,
            deviceId: $request->input('device_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Switched tenant successfully.',
            'data'    => [
                'access_token'  => $result['access_token'],
                'refresh_token' => $result['refresh_token'] ?? null,
                'token_type'    => 'Bearer',
                'expires_in'    => $result['expires_in'] ?? 0,
                'tenant'        => new TenantResource($result['tenant']),
            ],
        ]);
    }
}
