<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureFlagController extends BaseController
{
    protected FeatureFlagService $featureFlagService;

    public function __construct(FeatureFlagService $featureFlagService)
    {
        $this->featureFlagService = $featureFlagService;
    }

    /**
     * Get all enabled features.
     */
    public function index(): JsonResponse
    {
        try {
            $features = $this->featureFlagService->getEnabledFeatures();

            return $this->successResponse($features, 'Enabled features retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve features', $e->getMessage(), 500);
        }
    }

    /**
     * Check if a feature is enabled.
     */
    public function check(string $name): JsonResponse
    {
        try {
            $isEnabled = $this->featureFlagService->isEnabled($name);

            return $this->successResponse([
                'name' => $name,
                'enabled' => $isEnabled,
            ], 'Feature status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check feature', $e->getMessage(), 500);
        }
    }

    /**
     * Check multiple features at once.
     */
    public function checkMultiple(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'features' => 'required|array',
                'features.*' => 'required|string',
            ]);

            $result = $this->featureFlagService->checkFeatures($validated['features']);

            return $this->successResponse($result, 'Features checked successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check features', $e->getMessage(), 500);
        }
    }

    /**
     * Get features by module.
     */
    public function byModule(string $module): JsonResponse
    {
        try {
            $features = $this->featureFlagService->getModuleFeatures($module);

            return $this->successResponse($features, 'Module features retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve module features', $e->getMessage(), 500);
        }
    }

    /**
     * Enable a feature.
     */
    public function enable(string $name): JsonResponse
    {
        try {
            $result = $this->featureFlagService->enable($name);

            if (! $result) {
                return $this->errorResponse('Feature not found', null, 404);
            }

            return $this->successResponse(null, 'Feature enabled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to enable feature', $e->getMessage(), 500);
        }
    }

    /**
     * Disable a feature.
     */
    public function disable(string $name): JsonResponse
    {
        try {
            $result = $this->featureFlagService->disable($name);

            if (! $result) {
                return $this->errorResponse('Feature not found', null, 404);
            }

            return $this->successResponse(null, 'Feature disabled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to disable feature', $e->getMessage(), 500);
        }
    }

    /**
     * Create a feature flag.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:metadata_feature_flags,name',
                'label' => 'required|string',
                'description' => 'nullable|string',
                'is_enabled' => 'boolean',
                'module' => 'nullable|string',
                'config' => 'nullable|array',
            ]);

            $flag = $this->featureFlagService->create($validated);

            return $this->successResponse($flag, 'Feature flag created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create feature flag', $e->getMessage(), 500);
        }
    }

    /**
     * Update a feature flag.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'sometimes|string',
                'description' => 'nullable|string',
                'is_enabled' => 'boolean',
                'module' => 'nullable|string',
                'config' => 'nullable|array',
            ]);

            $result = $this->featureFlagService->update($id, $validated);

            if (! $result) {
                return $this->errorResponse('Feature flag not found', null, 404);
            }

            return $this->successResponse(null, 'Feature flag updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update feature flag', $e->getMessage(), 500);
        }
    }
}
