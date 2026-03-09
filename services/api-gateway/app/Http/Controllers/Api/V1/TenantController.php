<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TenantServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantConfigRequest;
use App\Http\Resources\Tenant\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Tenant Controller
 *
 * Thin controller - handles only HTTP request/response.
 * Business logic delegated to TenantService.
 */
class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService
    ) {}

    /**
     * Register a new tenant.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->register($request->validated());

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get current tenant info.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $this->tenantService->current();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant context found.',
            ], 404);
        }

        return (new TenantResource($tenant))->response();
    }

    /**
     * Update tenant runtime configuration.
     */
    public function updateConfig(UpdateTenantConfigRequest $request, string $tenantId): JsonResponse
    {
        $data = $request->validated();

        $success = $this->tenantService->updateConfig(
            $tenantId,
            $data['group'] ?? 'general',
            $data['config']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Configuration updated successfully.' : 'Failed to update configuration.',
        ]);
    }
}
