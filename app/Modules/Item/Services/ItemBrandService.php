<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Item\Models\ItemBrand;

final class ItemBrandService
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

        return $query->orderBy('name')->paginate(min($perPage, $lookup ? 50 : 100));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): ItemBrand
    {
        return $this->query($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function create(array $data, int $tenantId, ?int $organizationUnitId): ItemBrand
    {
        $this->assertUniqueCode($tenantId, (string) $data['code']);
        $this->assertUniqueName($tenantId, (string) $data['name']);

        return ItemBrand::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            ...$data,
        ]);
    }

    public function update(ItemBrand $brand, array $data): ItemBrand
    {
        if (isset($data['code'])) {
            $this->assertUniqueCode((int) $brand->tenant_id, (string) $data['code'], (int) $brand->getKey());
        }
        if (isset($data['name'])) {
            $this->assertUniqueName((int) $brand->tenant_id, (string) $data['name'], (int) $brand->getKey());
        }
        if (array_key_exists('is_active', $data) && $data['is_active'] === false && $brand->items()->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('Item brand cannot be deactivated while active items reference it.');
        }
        $brand->fill($data)->save();

        return $brand->refresh();
    }

    public function delete(ItemBrand $brand): void
    {
        if ($brand->items()->exists()) {
            throw new InvalidArgumentException('Item brand cannot be deleted while items reference it.');
        }
        $brand->delete();
    }

    private function assertUniqueCode(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = ItemBrand::query()->where('tenant_id', $tenantId)->where('code', $code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Item brand code already exists for this tenant.');
        }
    }

    private function assertUniqueName(int $tenantId, string $name, ?int $ignoreId = null): void
    {
        $query = ItemBrand::query()->where('tenant_id', $tenantId)->where('name', $name);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Item brand name already exists for this tenant.');
        }
    }

    private function query(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = ItemBrand::query()->where('tenant_id', $tenantId);
        if ($organizationUnitId !== null) {
            $query->where(fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId));
        } else {
            $query->whereNull('organization_unit_id');
        }

        return $query;
    }
}
