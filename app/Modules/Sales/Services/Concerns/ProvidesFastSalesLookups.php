<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use Modules\Payment\Models\PaymentMethod;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tax\Models\TaxGroup;

trait ProvidesFastSalesLookups
{
    /**
     * @return list<array<string, mixed>>
     */
    private function warehouseOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return $this->warehouses->activeWarehouseOptions($tenantId, $organizationUnitId, $search, $limit);
    }
    /**
     * @return list<array<string, mixed>>
     */
    private function currencyOptions(string $search, int $limit): array
    {
        return CurrencyModel::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'symbol', 'decimal_places'])
            ->map(fn (CurrencyModel $currency): array => ['id' => (int) $currency->getKey(), 'code' => $currency->code, 'name' => $currency->name, 'symbol' => $currency->symbol, 'decimal_places' => $currency->decimal_places])
            ->all();
    }
    /**
     * @return list<array<string, mixed>>
     */
    private function paymentMethodOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('direction_allowed', 'inbound')->orWhere('direction_allowed', 'both');
            })
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId !== null, fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'method_type', 'requires_reference', 'requires_instrument_details'])
            ->map(fn (PaymentMethod $method): array => ['id' => (int) $method->getKey(), 'code' => $method->code, 'name' => $method->name, 'method_type' => $this->enumValue($method->method_type), 'requires_reference' => (bool) $method->requires_reference, 'requires_instrument_details' => (bool) $method->requires_instrument_details])
            ->all();
    }
    /**
     * @return list<array<string, mixed>>
     */
    private function taxGroupOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return TaxGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'code', 'name', 'is_default'])
            ->map(fn (TaxGroup $group): array => ['id' => (int) $group->getKey(), 'code' => $group->code, 'name' => $group->name, 'is_default' => (bool) $group->is_default])
            ->all();
    }
}
