<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Sales\Validators\SalesValidationService;
use Modules\Tax\Models\TaxGroup;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Services\WarehouseDefaultResolver;

final class SalesDocumentContextService
{
    public function __construct(
        private readonly WarehouseDefaultResolver $warehouses,
        private readonly SalesValidationService $validator,
        private readonly ItemPriceResolutionService $prices,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function salesOrderCreateContext(int $tenantId, ?int $organizationUnitId): array
    {
        $warehouse = $this->warehouses->resolveDefaultWarehouse($tenantId, $organizationUnitId);
        $location = $warehouse === null ? null : $this->warehouses->resolveDefaultLocation($warehouse);
        $currency = CurrencyModel::query()->where('is_active', true)->orderBy('code')->first();

        return [
            'defaults' => [
                'sales_order_date' => now()->toDateString(),
                'exchange_rate' => '1.000000',
                'currency_id' => $currency?->getKey(),
                'currency' => $currency === null ? null : $this->summary($currency, ['code', 'name', 'symbol']),
                'warehouse_id' => $warehouse?->getKey(),
                'warehouse' => $warehouse === null ? null : $this->summary($warehouse, ['code', 'name']),
                'warehouse_location_id' => $location?->getKey(),
                'warehouse_location' => $location === null ? null : $this->summary($location, ['code', 'name']),
            ],
            'endpoints' => [
                'customer_search' => '/api/v1/customers/lookup/active',
                'item_search' => '/api/v1/items/lookup',
                'item_context' => '/api/v1/sales/items/{item}/sales-context',
                'adjustments_catalogue' => '/api/v1/sales/adjustments/catalogue',
                'warehouses' => '/api/v1/sales/warehouses',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function itemSalesContext(
        int $tenantId,
        ?int $organizationUnitId,
        int $itemId,
        ?int $variantId,
        ?int $currencyId,
        ?string $salesDate = null,
        ?int $uomId = null,
    ): array {
        $item = $this->validator->item($tenantId, $organizationUnitId, $itemId)
            ->load(['baseUom', 'salesTaxGroup', 'defaultTaxGroup', 'variants', 'units.uom']);

        if ($variantId !== null) {
            $this->validator->itemVariant($tenantId, $organizationUnitId, $itemId, $variantId);
        }

        $allowedUnits = $this->allowedSalesUnits($item, $organizationUnitId);
        $defaultUomId = $uomId ?? $this->defaultSalesUomId($item, $allowedUnits);
        $price = $this->prices->resolvePrice(
            item: $item,
            context: $this->priceContext($item),
            uomId: $defaultUomId,
            organizationUnitId: $organizationUnitId,
            currencyId: $currencyId,
            date: $salesDate ?? CarbonImmutable::now()->toDateString(),
            variantId: $variantId,
        );

        return [
            'item' => $this->itemSummary($item),
            'variants' => $item->variants
                ->filter(fn ($variant): bool => (bool) $variant->is_active
                    && ($variant->organization_unit_id === null || ($organizationUnitId !== null && (int) $variant->organization_unit_id === $organizationUnitId)))
                ->map(fn ($variant): array => [
                    'id' => (int) $variant->getKey(),
                    'code' => $variant->code ?? null,
                    'name' => $variant->name ?? null,
                    'sku' => $variant->sku ?? null,
                    'attributes' => $variant->attributes ?? [],
                ])
                ->values()
                ->all(),
            'default_sales_uom_id' => $price->uomId ?? $defaultUomId,
            'allowed_sales_uoms' => $allowedUnits,
            'quantity_precision' => $this->quantityPrecision($allowedUnits, $price->uomId ?? $defaultUomId),
            'unit_price' => $price->amount,
            'price_source' => $price->source,
            'price_source_label' => $this->priceSourceLabel($price->source),
            'pricing_mode' => $price->amount === null ? 'manual' : 'auto',
            'price_source_id' => $price->priceId,
            'currency_id' => $price->currencyId,
            'uom_id' => $price->uomId ?? $defaultUomId,
            'pricing_context_hash' => sha1(json_encode([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'item_id' => $itemId,
                'variant_id' => $variantId,
                'currency_id' => $currencyId,
                'uom_id' => $price->uomId ?? $defaultUomId,
                'date' => $salesDate,
                'source' => $price->source,
                'price_id' => $price->priceId,
            ], JSON_THROW_ON_ERROR)),
            'tax_defaults' => [
                'tax_group_id' => $item->sales_tax_group_id ?? $item->default_tax_group_id,
                'source' => $item->sales_tax_group_id === null ? 'item_default_tax' : 'item_sales_tax',
            ],
            'description' => $item->description ?? $item->name ?? null,
            'eligible' => true,
            'block_reason' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function warehouses(int $tenantId, ?int $organizationUnitId, string $search = '', int $limit = 25): array
    {
        return $this->warehouses->activeWarehouseOptions($tenantId, $organizationUnitId, $search, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function warehouseLocations(int $tenantId, ?int $organizationUnitId, int $warehouseId, string $search = '', int $limit = 25): array
    {
        return WarehouseLocationModel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn (Builder $query) => $query->whereNull('organization_unit_id'))
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_default')
            ->orderBy('path')
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get(['id', 'code', 'name', 'path', 'is_default'])
            ->map(fn (WarehouseLocationModel $location): array => [
                'id' => (int) $location->getKey(),
                'code' => $location->code,
                'name' => $location->name,
                'path' => $location->path,
                'is_default' => (bool) $location->is_default,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function taxGroups(int $tenantId, ?int $organizationUnitId, string $search = '', int $limit = 25): array
    {
        return TaxGroup::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->where('active', true)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('code')
            ->limit(max(1, $limit))
            ->get(['id', 'code', 'name'])
            ->map(fn (TaxGroup $group): array => [
                'id' => (int) $group->getKey(),
                'code' => $group->code,
                'name' => $group->name,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allowedSalesUnits(Item $item, ?int $organizationUnitId): array
    {
        return $item->units
            ->filter(fn (ItemUnit $unit): bool => (bool) $unit->is_active
                && ($unit->organization_unit_id === null || ($organizationUnitId !== null && (int) $unit->organization_unit_id === $organizationUnitId)))
            ->sort(function (ItemUnit $left, ItemUnit $right): int {
                $role = $this->unitRoleRank($left) <=> $this->unitRoleRank($right);
                if ($role !== 0) {
                    return $role;
                }

                $default = (int) $right->is_default <=> (int) $left->is_default;
                if ($default !== 0) {
                    return $default;
                }

                return (int) $left->getKey() <=> (int) $right->getKey();
            })
            ->map(fn (ItemUnit $unit): array => [
                'id' => (int) $unit->uom_id,
                'item_unit_id' => (int) $unit->getKey(),
                'unit_role' => $unit->unit_role instanceof ItemUnitRole ? $unit->unit_role->value : (string) $unit->unit_role,
                'is_default' => (bool) $unit->is_default,
                'conversion_factor' => (string) $unit->conversion_factor,
                'quantity_precision' => $unit->uom?->decimal_precision ?? 6,
                'uom' => $this->uomSummary($unit->uom),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $allowedUnits
     */
    private function defaultSalesUomId(Item $item, array $allowedUnits): ?int
    {
        $allowedIds = array_map(static fn (array $unit): int => (int) $unit['id'], $allowedUnits);

        foreach ($allowedUnits as $unit) {
            if ($unit['unit_role'] === ItemUnitRole::Sales->value && (bool) $unit['is_default']) {
                return (int) $unit['id'];
            }
        }

        foreach ($allowedUnits as $unit) {
            if ((bool) $unit['is_default']) {
                return (int) $unit['id'];
            }
        }

        if ($item->base_uom_id !== null && in_array((int) $item->base_uom_id, $allowedIds, true)) {
            return (int) $item->base_uom_id;
        }

        return $allowedUnits[0]['id'] ?? null;
    }

    private function unitRoleRank(ItemUnit $unit): int
    {
        $role = $unit->unit_role instanceof ItemUnitRole ? $unit->unit_role : ItemUnitRole::from((string) $unit->unit_role);

        return match ($role) {
            ItemUnitRole::Sales => 0,
            ItemUnitRole::Service => 1,
            ItemUnitRole::Base => 2,
            default => 3,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $allowedUnits
     */
    private function quantityPrecision(array $allowedUnits, ?int $defaultUomId): int
    {
        foreach ($allowedUnits as $unit) {
            if ((int) $unit['id'] === $defaultUomId) {
                return (int) ($unit['quantity_precision'] ?? 6);
            }
        }

        return 6;
    }

    private function priceContext(Item $item): string
    {
        return (string) ($item->item_type?->value ?? $item->item_type) === 'service'
            ? ItemPriceResolutionService::CONTEXT_SERVICE
            : ItemPriceResolutionService::CONTEXT_SALES;
    }

    private function priceSourceLabel(string $source): string
    {
        return match ($source) {
            'specific_price' => 'Sales price list',
            'standard_price' => 'Standard price',
            default => 'Manual price required',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSummary(Item $item): array
    {
        return [
            'id' => (int) $item->getKey(),
            'code' => $item->code,
            'sku' => $item->sku,
            'name' => $item->name,
            'base_uom_id' => $item->base_uom_id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function uomSummary(?UnitOfMeasureModel $uom): ?array
    {
        return $uom === null ? null : [
            'id' => (int) $uom->getKey(),
            'code' => $uom->code,
            'name' => $uom->name,
            'symbol' => $uom->symbol ?? null,
        ];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function summary(object $model, array $fields): array
    {
        $data = ['id' => method_exists($model, 'getKey') ? (int) $model->getKey() : null];
        foreach ($fields as $field) {
            $data[$field] = $model->{$field} ?? null;
        }

        return $data;
    }
}
