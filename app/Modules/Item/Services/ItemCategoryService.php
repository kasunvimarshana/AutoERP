<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Item\Models\ItemCategory;

final class ItemCategoryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, bool $lookup = false): LengthAwarePaginator
    {
        $query = $this->query($tenantId, $organizationUnitId);
        if ($lookup) {
            $query->where('is_active', true);
        } elseif (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null) {
            $query->where('is_active', $criteria['is_active']);
        }
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        return $query->with('parent')->orderBy('sort_order')->orderBy('name')->paginate(min($perPage, $lookup ? 50 : 100));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): ItemCategory
    {
        return $this->query($tenantId, $organizationUnitId)->with('parent')->findOrFail($id);
    }

    public function create(array $data, int $tenantId, ?int $organizationUnitId): ItemCategory
    {
        $this->validate($data, $tenantId, $organizationUnitId);

        return ItemCategory::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            ...$data,
        ])->load('parent');
    }

    public function update(ItemCategory $category, array $data): ItemCategory
    {
        $this->validate($data, (int) $category->tenant_id, $category->organization_unit_id, (int) $category->getKey());
        if (array_key_exists('is_active', $data) && $data['is_active'] === false) {
            $this->assertCanDeactivate($category);
        }
        $category->fill($data)->save();

        return $category->refresh()->load('parent');
    }

    public function delete(ItemCategory $category): void
    {
        if ($category->items()->exists()) {
            throw new InvalidArgumentException('Item category cannot be deleted while items reference it.');
        }
        if ($category->children()->exists()) {
            throw new InvalidArgumentException('Item category cannot be deleted while child categories reference it.');
        }
        $category->delete();
    }

    private function validate(array $data, int $tenantId, ?int $organizationUnitId, ?int $ignoreId = null): void
    {
        if (isset($data['code'])) {
            $duplicate = ItemCategory::query()->where('tenant_id', $tenantId)->where('code', $data['code']);
            if ($ignoreId !== null) {
                $duplicate->whereKeyNot($ignoreId);
            }
            if ($duplicate->exists()) {
                throw new InvalidArgumentException('Item category code already exists for this tenant.');
            }
        }

        if (isset($data['name'])) {
            $duplicate = ItemCategory::query()->where('tenant_id', $tenantId)->where('name', $data['name']);
            if ($ignoreId !== null) {
                $duplicate->whereKeyNot($ignoreId);
            }
            if ($duplicate->exists()) {
                throw new InvalidArgumentException('Item category name already exists for this tenant.');
            }
        }

        if (! empty($data['parent_id'])) {
            if ($ignoreId !== null && (int) $data['parent_id'] === $ignoreId) {
                throw new InvalidArgumentException('Item category cannot be its own parent.');
            }
            $parent = $this->query($tenantId, $organizationUnitId)->findOrFail((int) $data['parent_id']);
            if (! $parent->is_active) {
                throw new InvalidArgumentException('Inactive item category cannot be used as a parent.');
            }
            if ($ignoreId !== null && $this->wouldCreateCycle($ignoreId, (int) $parent->getKey())) {
                throw new InvalidArgumentException('Item category parent would create a hierarchy cycle.');
            }
        }
    }

    private function query(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = ItemCategory::query()->where('tenant_id', $tenantId);
        if ($organizationUnitId !== null) {
            $query->where(fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId));
        } else {
            $query->whereNull('organization_unit_id');
        }

        return $query;
    }

    private function wouldCreateCycle(int $categoryId, int $parentId): bool
    {
        $visited = [];
        $current = $parentId;

        while ($current > 0) {
            if ($current === $categoryId) {
                return true;
            }
            if (isset($visited[$current])) {
                return true;
            }
            $visited[$current] = true;
            $current = (int) (ItemCategory::query()->whereKey($current)->value('parent_id') ?? 0);
        }

        return false;
    }

    private function assertCanDeactivate(ItemCategory $category): void
    {
        if ($category->children()->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Item category cannot be deactivated while active child categories reference it.');
        }

        if ($category->items()->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Item category cannot be deactivated while active items reference it.');
        }
    }
}
