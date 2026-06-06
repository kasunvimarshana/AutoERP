<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;

final class ItemLookupService
{
    public function activeItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->active()->get();
    }

    public function stockItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->ofType($tenantId, ItemType::Stock, $organizationUnitId);
    }

    public function serviceItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->ofType($tenantId, ItemType::Service, $organizationUnitId);
    }

    public function labourItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->ofType($tenantId, ItemType::Labour, $organizationUnitId);
    }

    public function comboItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->ofType($tenantId, ItemType::Combo, $organizationUnitId);
    }

    public function packageItems(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->ofType($tenantId, ItemType::Package, $organizationUnitId);
    }

    private function ofType(int $tenantId, ItemType $type, ?int $organizationUnitId): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->where('item_type', $type->value)
            ->get();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Item::query()->forTenant($tenantId, $organizationUnitId);
    }
}
