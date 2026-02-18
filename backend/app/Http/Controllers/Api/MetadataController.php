<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\MetadataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetadataController extends BaseController
{
    protected MetadataService $metadataService;

    public function __construct(MetadataService $metadataService)
    {
        $this->metadataService = $metadataService;
    }

    /**
     * Get complete metadata catalog.
     */
    public function catalog(): JsonResponse
    {
        try {
            $catalog = $this->metadataService->getMetadataCatalog();

            return $this->successResponse($catalog, 'Metadata catalog retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve metadata catalog', $e->getMessage(), 500);
        }
    }

    /**
     * Get entity metadata by name.
     */
    public function entity(string $name): JsonResponse
    {
        try {
            $entity = $this->metadataService->getEntityMetadata($name);

            if (! $entity) {
                return $this->errorResponse('Entity not found', null, 404);
            }

            return $this->successResponse($entity, 'Entity metadata retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve entity metadata', $e->getMessage(), 500);
        }
    }

    /**
     * Get entities by module.
     */
    public function module(string $module): JsonResponse
    {
        try {
            $entities = $this->metadataService->getModuleEntities($module);

            return $this->successResponse($entities, 'Module entities retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve module entities', $e->getMessage(), 500);
        }
    }

    /**
     * Get field configuration for an entity.
     */
    public function fieldConfig(Request $request, string $entityName): JsonResponse
    {
        try {
            $context = $request->query('context', 'form'); // form, list, detail
            $config = $this->metadataService->getFieldConfig($entityName, $context);

            return $this->successResponse($config, 'Field configuration retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve field configuration', $e->getMessage(), 500);
        }
    }

    /**
     * Get validation rules for an entity.
     */
    public function validationRules(string $entityName): JsonResponse
    {
        try {
            $rules = $this->metadataService->getValidationRules($entityName);

            return $this->successResponse($rules, 'Validation rules retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve validation rules', $e->getMessage(), 500);
        }
    }

    /**
     * Clear metadata cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->metadataService->clearCache();

            return $this->successResponse(null, 'Metadata cache cleared successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear metadata cache', $e->getMessage(), 500);
        }
    }

    /**
     * Create entity metadata (admin only).
     */
    public function createEntity(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:metadata_entities,name',
                'label' => 'required|string',
                'label_plural' => 'nullable|string',
                'table_name' => 'nullable|string',
                'icon' => 'nullable|string',
                'module' => 'required|string',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'has_workflow' => 'boolean',
                'has_audit_trail' => 'boolean',
                'is_tenant_scoped' => 'boolean',
                'ui_config' => 'nullable|array',
                'api_config' => 'nullable|array',
                'permissions' => 'nullable|array',
            ]);

            $entity = $this->metadataService->createEntity($validated);

            return $this->successResponse($entity, 'Entity created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create entity', $e->getMessage(), 500);
        }
    }

    /**
     * Update entity metadata (admin only).
     */
    public function updateEntity(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'sometimes|string',
                'label_plural' => 'nullable|string',
                'icon' => 'nullable|string',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'has_workflow' => 'boolean',
                'has_audit_trail' => 'boolean',
                'ui_config' => 'nullable|array',
                'api_config' => 'nullable|array',
                'permissions' => 'nullable|array',
            ]);

            $result = $this->metadataService->updateEntity($id, $validated);

            if (! $result) {
                return $this->errorResponse('Entity not found or cannot be updated', null, 404);
            }

            return $this->successResponse(null, 'Entity updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update entity', $e->getMessage(), 500);
        }
    }
}
