<?php

namespace App\Services;

use App\Models\MetadataEntity;
use App\Repositories\MetadataEntityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class MetadataService extends BaseService
{
    protected MetadataEntityRepository $repository;

    public function __construct(MetadataEntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get entity metadata by name with caching.
     */
    public function getEntityMetadata(string $name): ?MetadataEntity
    {
        $cacheKey = "metadata:entity:{$name}";

        return Cache::remember($cacheKey, 3600, function () use ($name) {
            $entity = $this->repository->findByName($name);

            if ($entity) {
                $entity->load(['fields' => function ($query) {
                    $query->orderBy('order');
                }, 'workflows']);
            }

            return $entity;
        });
    }

    /**
     * Get all entities for a module.
     */
    public function getModuleEntities(string $module): Collection
    {
        $cacheKey = "metadata:module:{$module}";

        return Cache::remember($cacheKey, 3600, function () use ($module) {
            return $this->repository->getByModule($module);
        });
    }

    /**
     * Get complete metadata catalog.
     */
    public function getMetadataCatalog(): Collection
    {
        $cacheKey = 'metadata:catalog';

        return Cache::remember($cacheKey, 3600, function () {
            return $this->repository->getAllActiveWithFields();
        });
    }

    /**
     * Create entity metadata.
     */
    public function createEntity(array $data): MetadataEntity
    {
        return $this->transaction(function () use ($data) {
            $entity = $this->repository->create($data);

            // Clear cache
            Cache::forget('metadata:catalog');
            Cache::forget("metadata:module:{$entity->module}");

            $this->logInfo("Entity metadata created: {$entity->name}");

            return $entity;
        });
    }

    /**
     * Update entity metadata.
     */
    public function updateEntity(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $entity = $this->repository->find($id);

            if (! $entity) {
                return false;
            }

            $result = $this->repository->update($id, $data);

            // Clear cache
            Cache::forget("metadata:entity:{$entity->name}");
            Cache::forget("metadata:module:{$entity->module}");
            Cache::forget('metadata:catalog');

            $this->logInfo("Entity metadata updated: {$entity->name}");

            return $result;
        });
    }

    /**
     * Delete entity metadata.
     */
    public function deleteEntity(int $id): bool
    {
        $entity = $this->repository->find($id);

        if (! $entity || $entity->is_system) {
            return false;
        }

        return $this->transaction(function () use ($entity, $id) {
            $result = $this->repository->delete($id);

            // Clear cache
            Cache::forget("metadata:entity:{$entity->name}");
            Cache::forget("metadata:module:{$entity->module}");
            Cache::forget('metadata:catalog');

            $this->logInfo("Entity metadata deleted: {$entity->name}");

            return $result;
        });
    }

    /**
     * Get validation rules for an entity.
     */
    public function getValidationRules(string $entityName): array
    {
        $entity = $this->getEntityMetadata($entityName);

        if (! $entity) {
            return [];
        }

        $rules = [];

        foreach ($entity->fields as $field) {
            if ($field->is_visible_form && ! empty($field->validation_rules)) {
                $rules[$field->name] = $field->validation_rules;
            }
        }

        return $rules;
    }

    /**
     * Get field configuration for UI rendering.
     */
    public function getFieldConfig(string $entityName, string $context = 'form'): array
    {
        $entity = $this->getEntityMetadata($entityName);

        if (! $entity) {
            return [];
        }

        $visibilityField = "is_visible_{$context}";

        return $entity->fields
            ->where($visibilityField, true)
            ->sortBy('order')
            ->map(function ($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'required' => $field->is_required,
                    'readonly' => $field->is_readonly,
                    'default' => $field->default_value,
                    'options' => $field->options,
                    'ui_config' => $field->ui_config,
                    'validation' => $field->validation_rules,
                    'permissions' => $field->permissions,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Clear all metadata cache.
     */
    public function clearCache(): void
    {
        $entities = $this->repository->all();

        foreach ($entities as $entity) {
            Cache::forget("metadata:entity:{$entity->name}");
            Cache::forget("metadata:module:{$entity->module}");
        }

        Cache::forget('metadata:catalog');

        $this->logInfo('Metadata cache cleared');
    }
}
