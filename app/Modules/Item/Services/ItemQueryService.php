<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Item\Models\Item;

final class ItemQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with(['category', 'brand', 'baseUom']);
        $this->applyCriteria($query, $criteria);

        $sort = in_array(($criteria['sort'] ?? null), ['code', 'name', 'item_type', 'created_at'], true)
            ? (string) $criteria['sort']
            : 'name';
        $direction = ($criteria['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, string $kind): LengthAwarePaginator
    {
        $criteria['is_active'] = true;
        match ($kind) {
            'stockable' => $criteria['is_stockable'] = true,
            'service', 'labour', 'combo', 'package' => $criteria['item_type'] = $kind,
            default => null,
        };

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['category', 'brand', 'baseUom'])
            ->findOrFail($id);
    }

    public function item(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(Item $item): void
    {
        $item->delete();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Item::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        foreach (['item_type', 'is_stockable', 'is_active'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['category_id'])) {
            $query->where('item_category_id', (int) $criteria['category_id']);
        }
        if (! empty($criteria['brand_id'])) {
            $query->where('item_brand_id', (int) $criteria['brand_id']);
        }
        if (! empty($criteria['module_code'])) {
            $query->whereHas('usageRules', fn (Builder $rules): Builder => $rules
                ->where('module_code', (string) $criteria['module_code'])
                ->where('is_enabled', true));
        }
    }
}
