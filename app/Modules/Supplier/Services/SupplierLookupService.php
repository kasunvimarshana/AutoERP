<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Supplier\DTOs\SupplierResultData;
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

    public function preferredSuppliers(int $tenantId, int $itemId, ?int $organizationUnitId = null): Collection
    {
        return $this->preferredSuppliersForItem($tenantId, $itemId, $organizationUnitId);
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

    public function suppliersOnHold(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->where('status', SupplierStatus::OnHold)
            ->get();
    }

    public function blacklistedSuppliers(int $tenantId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->where('status', SupplierStatus::Blacklisted)
            ->get();
    }

    public function result(Supplier $supplier): SupplierResultData
    {
        return new SupplierResultData(
            supplierId: (int) $supplier->getKey(),
            tenantId: (int) $supplier->tenant_id,
            organizationUnitId: $supplier->organization_unit_id,
            supplierNumber: (string) $supplier->supplier_number,
            code: (string) $supplier->code,
            name: (string) $supplier->name,
            supplierType: $supplier->supplier_type,
            status: $supplier->status,
            creditLimit: (string) $supplier->credit_limit,
            openingBalance: (string) $supplier->opening_balance,
            isCreditAllowed: (bool) $supplier->is_credit_allowed,
            isAdvanceAllowed: (bool) $supplier->is_advance_allowed,
        );
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Supplier::query()->forTenant($tenantId, $organizationUnitId);
    }
}
