<?php

declare(strict_types=1);

namespace Modules\Customization\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\Customization\Domain\Contracts\CustomFieldValueRepositoryInterface;
use Modules\Customization\Domain\Entities\CustomFieldValue;
use Modules\Customization\Infrastructure\Models\CustomFieldValueModel;

class CustomFieldValueRepository extends BaseRepository implements CustomFieldValueRepositoryInterface
{
    protected function model(): string
    {
        return CustomFieldValueModel::class;
    }

    public function findByEntity(int $tenantId, string $entityType, int $entityId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('field_id')
            ->get()
            ->map(fn (CustomFieldValueModel $m) => $this->toDomain($m))
            ->all();
    }

    public function replaceForEntity(int $tenantId, string $entityType, int $entityId, array $values): array
    {
        return DB::transaction(function () use ($tenantId, $entityType, $entityId, $values): array {
            $this->newQuery()
                ->where('tenant_id', $tenantId)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->delete();

            $result = [];
            foreach ($values as $valueData) {
                $model = new CustomFieldValueModel;
                $model->tenant_id = $tenantId;
                $model->entity_type = $entityType;
                $model->entity_id = $entityId;
                $model->field_id = (int) $valueData['field_id'];
                $model->value = $valueData['value'] ?? null;
                $model->save();
                $result[] = $this->toDomain($model);
            }

            return $result;
        });
    }

    public function deleteByField(int $fieldId, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('field_id', $fieldId)
            ->delete();
    }

    private function toDomain(CustomFieldValueModel $model): CustomFieldValue
    {
        return new CustomFieldValue(
            id: $model->id,
            tenantId: $model->tenant_id,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            fieldId: $model->field_id,
            value: $model->value,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
