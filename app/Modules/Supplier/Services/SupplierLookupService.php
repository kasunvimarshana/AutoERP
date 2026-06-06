<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;

final class SupplierLookupService
{
    public function activeSuppliers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->active()->get();
    }

    public function suppliersByCategory(int $tenantId, int $categoryId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($categoryId)->where('is_active', true))
            ->get();
    }

    public function suppliersByItem(int $tenantId, int $itemId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->whereHas('itemMappings', fn (Builder $query): Builder => $query
                ->where('item_id', $itemId)
                ->where('is_active', true))
            ->get();
    }

    public function preferredSuppliersForItem(int $tenantId, int $itemId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->whereHas('itemMappings', fn (Builder $query): Builder => $query
                ->where('item_id', $itemId)
                ->where('is_preferred', true)
                ->where('is_active', true))
            ->get();
    }

    public function suppliersAllowedForCredit(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->active()
            ->where('is_credit_allowed', true)
            ->get();
    }

    public function restrictedSuppliers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->whereIn('status', [SupplierStatus::OnHold->value, SupplierStatus::Blacklisted->value])
            ->get();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Supplier::query()->forTenant($tenantId, $organizationUnitId);
    }
}
