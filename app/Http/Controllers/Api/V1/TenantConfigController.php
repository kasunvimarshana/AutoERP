<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantConfigResource;
use App\Services\TenantConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TenantConfigController extends Controller
{
    public function __construct(
        private readonly TenantConfigService $tenantConfigService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/tenants/{tenantId}/config",
     *     summary="Get all runtime configuration for a tenant",
     *     tags={"Tenant Config"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request, string $tenantId): JsonResponse
    {
        $configs = $this->tenantConfigService->getAllForTenant($tenantId);

        return response()->json([
            'success' => true,
            'data'    => $configs,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/tenants/{tenantId}/config/{key}",
     *     summary="Set a runtime configuration value for a tenant",
     *     tags={"Tenant Config"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function set(Request $request, string $tenantId, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value'        => ['required'],
            'type'         => ['sometimes', 'string', 'in:string,boolean,integer,float,json'],
            'group'        => ['sometimes', 'string', 'max:100'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $config = $this->tenantConfigService->set(
            tenantId: $tenantId,
            key: $key,
            value: $validated['value'],
            options: array_filter([
                'type'         => $validated['type'] ?? 'string',
                'group'        => $validated['group'] ?? 'general',
                'is_sensitive' => $validated['is_sensitive'] ?? false,
                'description'  => $validated['description'] ?? null,
            ])
        );

        return response()->json([
            'success' => true,
            'data'    => new TenantConfigResource($config),
            'message' => 'Configuration updated.',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/tenants/{tenantId}/config/{key}",
     *     summary="Remove a runtime configuration key for a tenant",
     *     tags={"Tenant Config"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy(Request $request, string $tenantId, string $key): JsonResponse
    {
        $this->tenantConfigService->delete($tenantId, $key);

        return response()->json([
            'success' => true,
            'message' => 'Configuration key removed.',
        ]);
    }
}
