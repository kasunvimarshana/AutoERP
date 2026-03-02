<?php

declare(strict_types=1);

namespace Modules\Metadata\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Metadata\Application\DTOs\CreateCustomFieldDTO;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;

/**
 * Metadata service.
 *
 * Orchestrates custom field and feature flag use cases.
 */
class MetadataService implements ServiceContract
{
    public function __construct(
        private readonly CustomFieldRepositoryContract $fieldRepository,
        private readonly FeatureFlagRepositoryContract $flagRepository,
    ) {}

    /**
     * Return all active custom fields for a given entity type.
     */
    public function listFields(string $entityType): Collection
    {
        return $this->fieldRepository->findByEntityType($entityType);
    }

    /**
     * Return a paginated list of all custom field definitions.
     */
    public function paginateFields(int $perPage = 15): LengthAwarePaginator
    {
        return $this->fieldRepository->paginate($perPage);
    }

    /**
     * Create a new custom field definition.
     */
    public function createField(CreateCustomFieldDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->fieldRepository->create([
                'entity_type'      => $dto->entityType,
                'field_name'       => $dto->fieldName,
                'field_label'      => $dto->fieldLabel,
                'field_type'       => $dto->fieldType,
                'options'          => $dto->options,
                'is_required'      => $dto->isRequired,
                'is_active'        => $dto->isActive,
                'sort_order'       => $dto->sortOrder,
                'validation_rules' => $dto->validationRules,
            ]);
        });
    }

    /**
     * Show a single custom field definition.
     */
    public function showField(int|string $id): Model
    {
        return $this->fieldRepository->findOrFail($id);
    }

    /**
     * Update an existing custom field definition.
     *
     * @param array<string, mixed> $data
     */
    public function updateField(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->fieldRepository->update($id, $data);
        });
    }

    /**
     * Delete a custom field definition.
     */
    public function deleteField(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->fieldRepository->delete($id);
        });
    }

    /**
     * Check whether a feature flag is enabled for the current tenant.
     *
     * Resolves from the feature_flags table; defaults to false if not set.
     */
    public function isFeatureEnabled(string $flagKey): bool
    {
        return $this->flagRepository->isFlagEnabled($flagKey);
    }

    /**
     * Return all feature flags for the current tenant.
     */
    public function listFlags(): Collection
    {
        return $this->flagRepository->all();
    }

    /**
     * Create a new feature flag.
     *
     * @param array<string, mixed> $data
     */
    public function createFlag(array $data): \Modules\Metadata\Domain\Entities\FeatureFlag
    {
        return DB::transaction(function () use ($data): \Modules\Metadata\Domain\Entities\FeatureFlag {
            /** @var \Modules\Metadata\Domain\Entities\FeatureFlag $flag */
            $flag = $this->flagRepository->create([
                'flag_key'    => $data['flag_key'],
                'flag_value'  => (bool) ($data['flag_value'] ?? false),
                'description' => $data['description'] ?? null,
            ]);

            return $flag;
        });
    }

    /**
     * Update an existing feature flag.
     *
     * @param array<string, mixed> $data
     */
    public function updateFlag(int|string $id, array $data): \Modules\Metadata\Domain\Entities\FeatureFlag
    {
        return DB::transaction(function () use ($id, $data): \Modules\Metadata\Domain\Entities\FeatureFlag {
            /** @var \Modules\Metadata\Domain\Entities\FeatureFlag $flag */
            $flag = $this->flagRepository->update($id, $data);

            return $flag;
        });
    }

    /**
     * Delete a feature flag by ID.
     */
    public function deleteFlag(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->flagRepository->delete($id);
        });
    }

    /**
     * Toggle a feature flag on/off.
     *
     * Inverts the current flag_value and persists the change.
     */
    public function toggleFlag(int|string $id): \Modules\Metadata\Domain\Entities\FeatureFlag
    {
        return DB::transaction(function () use ($id): \Modules\Metadata\Domain\Entities\FeatureFlag {
            /** @var \Modules\Metadata\Domain\Entities\FeatureFlag $flag */
            $flag = $this->flagRepository->findOrFail($id);
            $flag->update(['flag_value' => ! $flag->flag_value]);

            return $flag->fresh();
        });
    }
}
