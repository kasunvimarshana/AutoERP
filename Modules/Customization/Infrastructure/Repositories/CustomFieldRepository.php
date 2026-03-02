<?php

declare(strict_types=1);

namespace Modules\Customization\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Entities\CustomField;
use Modules\Customization\Infrastructure\Models\CustomFieldModel;

class CustomFieldRepository extends BaseRepository implements CustomFieldRepositoryInterface
{
    protected function model(): string
    {
        return CustomFieldModel::class;
    }

    public function findById(int $id, int $tenantId): ?CustomField
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, string $entityType, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (CustomFieldModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByEntityType(int $tenantId, string $entityType): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CustomFieldModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(CustomField $field): CustomField
    {
        if ($field->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $field->tenantId)
                ->findOrFail($field->id);
        } else {
            $model = new CustomFieldModel;
            $model->tenant_id = $field->tenantId;
            $model->entity_type = $field->entityType;
            $model->field_key = $field->fieldKey;
            $model->field_type = $field->fieldType;
        }

        $model->field_label = $field->fieldLabel;
        $model->is_required = $field->isRequired;
        $model->default_value = $field->defaultValue;
        $model->sort_order = $field->sortOrder;
        $model->options = $field->options;
        $model->validation_rules = $field->validationRules;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        if ($model === null) {
            throw new \DomainException('Custom field not found.');
        }

        $model->delete();
    }

    private function toDomain(CustomFieldModel $model): CustomField
    {
        return new CustomField(
            id: $model->id,
            tenantId: $model->tenant_id,
            entityType: $model->entity_type,
            fieldKey: $model->field_key,
            fieldLabel: $model->field_label,
            fieldType: $model->field_type,
            isRequired: (bool) $model->is_required,
            defaultValue: $model->default_value,
            sortOrder: (int) $model->sort_order,
            options: $model->options,
            validationRules: $model->validation_rules,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
