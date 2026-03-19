<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureFlagResource;
use App\Services\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly FeatureFlagService $featureFlagService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/admin/feature-flags",
     *     summary="List feature flags for a tenant",
     *     tags={"Feature Flags"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $flags = $this->featureFlagService->listForTenant(
            is_string($tenantId) ? $tenantId : null
        );

        return response()->json([
            'success' => true,
            'data'    => FeatureFlagResource::collection(collect($flags)),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/feature-flags",
     *     summary="Create or update a feature flag",
     *     tags={"Feature Flags"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:150'],
            'is_enabled'         => ['required', 'boolean'],
            'tenant_id'          => ['nullable', 'uuid'],
            'rollout_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'conditions'         => ['sometimes', 'nullable', 'array'],
            'description'        => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $tenantId = isset($validated['tenant_id']) ? (string) $validated['tenant_id'] : null;

        $flag = $this->featureFlagService->upsert(
            flagName: $validated['name'],
            isEnabled: $validated['is_enabled'],
            tenantId: $tenantId,
            data: array_filter([
                'rollout_percentage' => $validated['rollout_percentage'] ?? 100,
                'conditions'         => $validated['conditions'] ?? null,
                'description'        => $validated['description'] ?? null,
            ])
        );

        return response()->json([
            'success' => true,
            'data'    => new FeatureFlagResource($flag),
            'message' => 'Feature flag saved.',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/feature-flags/{name}",
     *     summary="Delete a feature flag",
     *     tags={"Feature Flags"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy(Request $request, string $name): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $this->featureFlagService->delete(
            $name,
            is_string($tenantId) ? $tenantId : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Feature flag deleted.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/feature-flags/{name}/check",
     *     summary="Check if a feature flag is enabled for the current user/tenant",
     *     tags={"Feature Flags"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function check(Request $request, string $name): JsonResponse
    {
        $user     = $request->user();
        $tenantId = $user?->tenant_id ?? $request->query('tenant_id');

        $context = [
            'user_id'   => $user?->id,
            'tenant_id' => $tenantId,
        ];

        $enabled = $this->featureFlagService->isEnabled(
            $name,
            is_string($tenantId) ? $tenantId : null,
            $context
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'name'       => $name,
                'is_enabled' => $enabled,
            ],
        ]);
    }
}
