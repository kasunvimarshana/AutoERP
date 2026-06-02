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

        if (! $model instanceof Model) {
            return null;
        }

        $records = $this->recordsFromModels([$model]);

        return $records[0] ?? null;
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
        return $this->recordsFromModels($query->get()->all());
    }

    public function page(array $criteria, int $perPage, int $page, array $with = []): PagedResult
    {
        $query = $this->applyItemCriteria($this->query($with), $criteria);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $this->recordsFromModels($paginator->items());

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    /**
     * @param array<int, mixed> $models
     * @return list<DataRecord>
     */
    private function recordsFromModels(array $models): array
    {
        $rows = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $rows[] = $model->toArray();
            }
        }

        if ($rows === []) {
            return [];
        }

        $categoryMap = $this->lookupLabels('item_categories', $this->idsFromRows($rows, 'category_id'), ['name']);
        $brandMap = $this->lookupLabels('item_brands', $this->idsFromRows($rows, 'brand_id'), ['name']);
        $typeMap = $this->lookupLabels('item_types', $this->idsFromRows($rows, 'item_type_id'), ['name', 'code']);
        $accountMap = $this->lookupLabels('accounts', $this->accountIdsFromRows($rows), ['code', 'name']);
        $uomMap = $this->lookupLabels('unit_of_measures', $this->uomIdsFromRows($rows), ['name', 'code', 'symbol']);

        $records = [];
        foreach ($rows as $row) {
            $categoryId = isset($row['category_id']) ? (int) $row['category_id'] : null;
            $brandId = isset($row['brand_id']) ? (int) $row['brand_id'] : null;
            $typeId = isset($row['item_type_id']) ? (int) $row['item_type_id'] : null;

            if ($categoryId !== null && isset($categoryMap[$categoryId])) {
                $row['category_name'] = $categoryMap[$categoryId]['name'] ?? null;
            }

            if ($brandId !== null && isset($brandMap[$brandId])) {
                $row['brand_name'] = $brandMap[$brandId]['name'] ?? null;
            }

            if ($typeId !== null && isset($typeMap[$typeId])) {
                $row['item_type_name'] = $typeMap[$typeId]['name'] ?? null;
                $row['item_type_code'] = $typeMap[$typeId]['code'] ?? null;
            }

            foreach (['income_account', 'expense_account', 'inventory_account', 'cogs_account'] as $prefix) {
                $id = isset($row[$prefix . '_id']) ? (int) $row[$prefix . '_id'] : null;
                if ($id === null || ! isset($accountMap[$id])) {
                    continue;
                }

                $accountCode = trim((string) ($accountMap[$id]['code'] ?? ''));
                $accountName = trim((string) ($accountMap[$id]['name'] ?? ''));
                $row[$prefix . '_name'] = $accountName !== '' ? $accountName : null;
                $row[$prefix . '_code'] = $accountCode !== '' ? $accountCode : null;
                $row[$prefix . '_label'] = trim($accountCode . ' - ' . $accountName, ' -');
            }

            foreach (['base_uom', 'default_receipt_uom', 'default_issue_uom', 'default_consumption_uom', 'default_charge_uom'] as $prefix) {
                $id = isset($row[$prefix . '_id']) ? (int) $row[$prefix . '_id'] : null;
                if ($id === null || ! isset($uomMap[$id])) {
                    continue;
                }

                $row[$prefix . '_name'] = $uomMap[$id]['name'] ?? null;
                $row[$prefix . '_code'] = $uomMap[$id]['code'] ?? null;
                $row[$prefix . '_symbol'] = $uomMap[$id]['symbol'] ?? null;
            }

            $records[] = new DataRecord($row);
        }

        return $records;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<int>
     */
    private function idsFromRows(array $rows, string $column): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if (isset($row[$column]) && is_numeric($row[$column])) {
                $ids[] = (int) $row[$column];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<int>
     */
    private function uomIdsFromRows(array $rows): array
    {
        $ids = [];
        foreach (['base_uom_id', 'default_receipt_uom_id', 'default_issue_uom_id', 'default_consumption_uom_id', 'default_charge_uom_id'] as $column) {
            array_push($ids, ...$this->idsFromRows($rows, $column));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<int>
     */
    private function accountIdsFromRows(array $rows): array
    {
        $ids = [];
        foreach (['income_account_id', 'expense_account_id', 'inventory_account_id', 'cogs_account_id'] as $column) {
            array_push($ids, ...$this->idsFromRows($rows, $column));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<int> $ids
     * @param list<string> $columns
     * @return array<int, array<string, mixed>>
     */
    private function lookupLabels(string $table, array $ids, array $columns): array
    {
        if ($ids === []) {
            return [];
        }

        $select = array_merge(['id'], $columns);
        $rows = DB::table($table)
            ->whereIn('id', $ids)
            ->get($select);

        $map = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($select as $column) {
                $values[$column] = $row->{$column} ?? null;
            }
            $map[(int) $row->id] = $values;
        }

        return $map;
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
