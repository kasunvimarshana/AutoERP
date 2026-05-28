<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class EloquentItemRepository extends EloquentRepository implements ItemRepositoryInterface
{
    public function __construct(ItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()->where('tenant_id', $tenantId)->find($id);

        return $model === null ? null : $this->toRecord($model);
    }

    /**
     * @param list<array<string, mixed>> $values
     */
    public function syncMetadataValues(int $tenantId, int $itemId, array $values): void
    {
        DB::table('item_metadata_values')
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->delete();

        foreach ($values as $value) {
            $definitionId = $this->resolveMetadataDefinitionId($tenantId, $value);
            if ($definitionId === null) {
                continue;
            }

            $typed = $this->normalizeMetadataValue($value['value'] ?? null);

            DB::table('item_metadata_values')->insert([
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'definition_id' => $definitionId,
                'value_string' => $typed['value_string'],
                'value_number' => $typed['value_number'],
                'value_boolean' => $typed['value_boolean'],
                'value_date' => $typed['value_date'],
                'value_datetime' => $typed['value_datetime'],
                'value_json' => $typed['value_json'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<DataRecord>
     */
    public function list(array $criteria = [], array $with = []): array
    {
        $query = $this->applyItemCriteria($this->query($with), $criteria);
        $models = $query->get();

        $records = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function page(array $criteria, int $perPage, int $page, array $with = []): PagedResult
    {
        $query = $this->applyItemCriteria($this->query($with), $criteria);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    private function applyItemCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($column === 'search') {
                $search = trim((string) $value);
                if ($search === '') {
                    continue;
                }

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%');
                });
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    $query->whereRaw('1 = 0');
                    continue;
                }

                $query->whereIn($column, $value);
                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $value
     */
    private function resolveMetadataDefinitionId(int $tenantId, array $value): ?int
    {
        if (isset($value['definition_id']) && is_numeric($value['definition_id'])) {
            return (int) $value['definition_id'];
        }

        $fieldKey = isset($value['field_key']) ? trim((string) $value['field_key']) : '';
        if ($fieldKey === '') {
            return null;
        }

        $existing = DB::table('item_metadata_definitions')
            ->where('tenant_id', $tenantId)
            ->where('field_key', $fieldKey)
            ->whereNull('deleted_at')
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        $valueType = isset($value['value_type']) ? (string) $value['value_type'] : null;
        $now = now();
        $id = DB::table('item_metadata_definitions')->insertGetId([
            'tenant_id' => $tenantId,
            'field_key' => $fieldKey,
            'label' => isset($value['label']) ? (string) $value['label'] : ucfirst(str_replace('_', ' ', $fieldKey)),
            'value_type' => $valueType,
            'is_required' => (bool) ($value['is_required'] ?? false),
            'sort_order' => (int) ($value['sort_order'] ?? 0),
            'is_active' => isset($value['is_active']) ? (bool) $value['is_active'] : true,
            'metadata' => isset($value['metadata']) && is_array($value['metadata'])
                ? json_encode($value['metadata'])
                : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMetadataValue(mixed $value): array
    {
        $normalized = [
            'value_string' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_json' => null,
        ];

        if ($value === null) {
            return $normalized;
        }

        if (is_bool($value)) {
            $normalized['value_boolean'] = $value;

            return $normalized;
        }

        if (is_int($value) || is_float($value)) {
            $normalized['value_number'] = (float) $value;

            return $normalized;
        }

        if (is_array($value)) {
            $normalized['value_json'] = json_encode($value);

            return $normalized;
        }

        $normalized['value_string'] = (string) $value;

        return $normalized;
    }
}
