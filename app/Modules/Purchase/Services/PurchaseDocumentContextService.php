<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Validators\PurchaseValidationService;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;
use Modules\Tax\Models\SupplierTaxProfile;
use Modules\Tenant\Models\TenantModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDefaultResolver;

final class PurchaseDocumentContextService
{
    public function __construct(
        private readonly WarehouseDefaultResolver $warehouseDefaults,
        private readonly PurchaseValidationService $validator,
        private readonly ItemPriceResolutionService $prices,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrderCreateContext(int $tenantId, ?int $organizationUnitId): array
    {
        $tenant = TenantModel::query()->with('currency')->findOrFail($tenantId);
        $currency = $tenant->currency instanceof CurrencyModel ? $tenant->currency : null;
        $warehouse = $this->warehouseDefaults->resolveDefaultWarehouse($tenantId, $organizationUnitId);
        $location = $warehouse instanceof WarehouseModel
            ? $this->warehouseDefaults->resolveDefaultLocation($warehouse)
            : null;

        return [
            'defaults' => [
                'purchase_order_date' => CarbonImmutable::now()->toDateString(),
                'expected_delivery_date' => null,
                'currency_id' => $currency?->getKey(),
                'currency' => $this->currencySummary($currency),
                'currency_source' => $currency instanceof CurrencyModel ? 'tenant_default' : 'none',
                'exchange_rate' => '1.000000',
                'exchange_rate_source' => 'tenant_default',
                'warehouse_id' => $warehouse?->getKey(),
                'warehouse' => $this->warehouseSummary($warehouse),
                'warehouse_source' => $warehouse instanceof WarehouseModel ? 'organization_unit_default' : 'none',
                'warehouse_location_id' => $location?->getKey(),
                'warehouse_location' => $this->locationSummary($location),
                'warehouse_location_source' => $location instanceof WarehouseLocationModel ? 'warehouse_default' : 'none',
            ],
            'exchange_rate_context' => [
                'base_currency_id' => $currency?->getKey(),
                'selected_currency_id' => $currency?->getKey(),
                'base_currency_uses_rate_one' => true,
                'foreign_currency_behavior' => 'manual_required',
                'override_allowed' => true,
            ],
            'payment_terms' => [
                'options' => [],
                'default' => null,
            ],
            'allowed_overrides' => [
                'currency' => true,
                'exchange_rate' => true,
                'warehouse' => true,
                'warehouse_location' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supplierContext(int $tenantId, ?int $organizationUnitId, int $supplierId): array
    {
        $supplier = $this->validator->supplier($tenantId, $organizationUnitId, $supplierId, 'supplier_id')
            ->load(['defaultCurrency', 'contacts']);

        $taxProfile = SupplierTaxProfile::query()
            ->with('taxGroup')
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplier->getKey())
            ->where('active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->orderByRaw($organizationUnitId === null ? 'organization_unit_id asc' : 'case when organization_unit_id = ? then 0 else 1 end', $organizationUnitId === null ? [] : [$organizationUnitId])
            ->first();

        return [
            'supplier' => $this->supplierSummary($supplier),
            'currency_id' => $supplier->default_currency_id,
            'currency' => $this->currencySummary($supplier->defaultCurrency),
            'currency_source' => $supplier->default_currency_id === null ? 'none' : 'supplier_default',
            'payment_term_id' => $supplier->payment_term_id,
            'payment_terms_source' => $supplier->payment_term_id === null ? 'none' : 'supplier_default',
            'tax_profile' => $taxProfile instanceof SupplierTaxProfile ? [
                'id' => (int) $taxProfile->getKey(),
                'tax_group_id' => $taxProfile->tax_group_id,
                'tax_group' => $taxProfile->relationLoaded('taxGroup') ? $this->namedSummary($taxProfile->taxGroup) : null,
            ] : null,
            'delivery_terms' => $supplier->metadata['delivery_terms'] ?? null,
            'purchasing_contact' => $supplier->contacts->first() === null ? null : $this->namedSummary($supplier->contacts->first()),
            'supplier_item_mapping_context' => [
                'active_mapping_count' => SupplierItemMapping::query()
                    ->where('tenant_id', $tenantId)
                    ->where('supplier_id', $supplier->getKey())
                    ->where('is_active', true)
                    ->count(),
            ],
            'warning' => 'Changing the supplier may refresh line UOM, price, and tax defaults.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function itemPurchaseContext(
        int $tenantId,
        ?int $organizationUnitId,
        int $itemId,
        ?int $supplierId,
        ?int $variantId,
        ?int $currencyId,
    ): array {
        $item = $this->validator->item($tenantId, $organizationUnitId, $itemId, 'item_id')
            ->load(['baseUom', 'purchaseTaxGroup', 'defaultTaxGroup', 'variants', 'units.uom']);

        if ($variantId !== null) {
            $this->validator->itemVariant($tenantId, $organizationUnitId, $itemId, $variantId, 'item_variant_id');
        }

        $allowedUnits = $this->allowedPurchaseUnits($item, $organizationUnitId);
        $mapping = $supplierId === null ? null : $this->supplierMapping($tenantId, $organizationUnitId, $supplierId, $itemId, $variantId);
        $defaultUomId = $this->defaultPurchaseUomId($item, $allowedUnits, $mapping);
        $price = $this->resolvePrice($item, $organizationUnitId, $currencyId, $variantId, $defaultUomId, $supplierId);

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
            'default_purchase_uom_id' => $defaultUomId,
            'allowed_purchase_uoms' => $allowedUnits,
            'quantity_precision' => $this->quantityPrecision($allowedUnits, $defaultUomId),
            'unit_price' => $price['amount'],
            'price_source' => $price['source'],
            'price_source_label' => $price['label'],
            'tax_defaults' => [
                'tax_group_id' => $item->purchase_tax_group_id ?? $item->default_tax_group_id,
                'source' => $item->purchase_tax_group_id === null ? 'item_default_tax' : 'item_purchase_tax',
            ],
            'description' => $item->description ?? $item->name ?? null,
            'supplier_mapping' => $mapping === null ? null : [
                'id' => (int) $mapping->getKey(),
                'default_purchase_uom_id' => $mapping->default_purchase_uom_id,
                'minimum_order_quantity' => (string) $mapping->minimum_order_quantity,
                'lead_time_days' => $mapping->lead_time_days,
                'is_preferred' => (bool) $mapping->is_preferred,
            ],
            'eligible' => true,
            'block_reason' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function warehouses(int $tenantId, ?int $organizationUnitId, string $search = '', int $limit = 25): array
    {
        return $this->warehouseDefaults->activeWarehouseOptions($tenantId, $organizationUnitId, $search, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function warehouseLocations(int $tenantId, ?int $organizationUnitId, int $warehouseId, string $search = '', int $limit = 50): array
    {
        $this->validator->warehouse($tenantId, $organizationUnitId, $warehouseId, 'warehouse_id');

        return WarehouseLocationModel::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
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
    private function allowedPurchaseUnits(Item $item, ?int $organizationUnitId): array
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

    private function defaultPurchaseUomId(Item $item, array $allowedUnits, ?SupplierItemMapping $mapping): ?int
    {
        $allowedIds = array_map(static fn (array $unit): int => (int) $unit['id'], $allowedUnits);

        if ($mapping?->default_purchase_uom_id !== null && in_array((int) $mapping->default_purchase_uom_id, $allowedIds, true)) {
            return (int) $mapping->default_purchase_uom_id;
        }

        foreach ($allowedUnits as $unit) {
            if ($unit['unit_role'] === ItemUnitRole::Purchase->value && (bool) $unit['is_default']) {
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

    private function supplierMapping(
        int $tenantId,
        ?int $organizationUnitId,
        int $supplierId,
        int $itemId,
        ?int $variantId,
    ): ?SupplierItemMapping {
        return SupplierItemMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->where('item_id', $itemId)
            ->where('is_active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId)),
            )
            ->when(
                $variantId === null,
                fn (Builder $query) => $query->whereNull('item_variant_id'),
                fn (Builder $query) => $query->where(fn (Builder $scope): Builder => $scope
                    ->whereNull('item_variant_id')
                    ->orWhere('item_variant_id', $variantId)),
            )
            ->when($variantId !== null, fn (Builder $query) => $query->orderByRaw('case when item_variant_id = ? then 0 else 1 end', [$variantId]))
            ->orderByDesc('is_preferred')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{amount: ?string, source: string, label: string}
     */
    private function resolvePrice(
        Item $item,
        ?int $organizationUnitId,
        ?int $currencyId,
        ?int $variantId,
        ?int $uomId,
        ?int $supplierId,
    ): array {
        $resolved = $this->prices->resolvePrice(
            $item,
            ItemPriceResolutionService::CONTEXT_PURCHASE,
            $uomId,
            $organizationUnitId,
            $currencyId,
            CarbonImmutable::now()->toDateString(),
            $variantId,
        );

        if ($resolved->amount !== null) {
            return [
                'amount' => $resolved->amount,
                'source' => 'purchase_price_list',
                'label' => 'Purchase price list',
            ];
        }

        $last = PurchaseOrderLine::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->where('purchase_order_lines.tenant_id', $item->tenant_id)
            ->where('purchase_order_lines.item_id', $item->getKey())
            ->where('purchase_order_lines.uom_id', $uomId)
            ->when($supplierId !== null, fn ($query) => $query->where('purchase_orders.supplier_id', $supplierId))
            ->when($variantId === null, fn ($query) => $query->whereNull('purchase_order_lines.item_variant_id'), fn ($query) => $query->where('purchase_order_lines.item_variant_id', $variantId))
            ->when($currencyId === null, fn ($query) => $query->whereNull('purchase_orders.currency_id'), fn ($query) => $query->where('purchase_orders.currency_id', $currencyId))
            ->whereNotIn('purchase_orders.status', [
                PurchaseOrderStatus::Draft->value,
                PurchaseOrderStatus::Cancelled->value,
            ])
            ->orderByDesc('purchase_orders.purchase_order_date')
            ->orderByDesc('purchase_order_lines.id')
            ->first(['purchase_order_lines.unit_price', 'purchase_orders.purchase_order_date']);

        if ($last instanceof PurchaseOrderLine) {
            $date = $last->purchase_order_date === null
                ? 'previous purchase'
                : CarbonImmutable::parse((string) $last->purchase_order_date)->format('d M Y');

            return [
                'amount' => (string) $last->unit_price,
                'source' => 'last_purchase_price',
                'label' => 'Last purchased on '.$date,
            ];
        }

        return [
            'amount' => null,
            'source' => 'manual',
            'label' => 'Manual price required',
        ];
    }

    private function quantityPrecision(array $allowedUnits, ?int $defaultUomId): int
    {
        foreach ($allowedUnits as $unit) {
            if ((int) $unit['id'] === $defaultUomId) {
                return (int) ($unit['quantity_precision'] ?? 6);
            }
        }

        return 6;
    }

    private function unitRoleRank(ItemUnit $unit): int
    {
        $role = $unit->unit_role instanceof ItemUnitRole ? $unit->unit_role : ItemUnitRole::from((string) $unit->unit_role);

        return match ($role) {
            ItemUnitRole::Purchase => 0,
            ItemUnitRole::Base => 1,
            default => 2,
        };
    }

    private function currencySummary(?CurrencyModel $currency): ?array
    {
        return $currency === null ? null : [
            'id' => (int) $currency->getKey(),
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol ?? null,
        ];
    }

    private function warehouseSummary(?WarehouseModel $warehouse): ?array
    {
        return $warehouse === null ? null : [
            'id' => (int) $warehouse->getKey(),
            'code' => $warehouse->code,
            'name' => $warehouse->name,
        ];
    }

    private function locationSummary(?WarehouseLocationModel $location): ?array
    {
        return $location === null ? null : [
            'id' => (int) $location->getKey(),
            'code' => $location->code,
            'name' => $location->name,
            'path' => $location->path,
        ];
    }

    private function supplierSummary(Supplier $supplier): array
    {
        return [
            'id' => (int) $supplier->getKey(),
            'supplier_number' => $supplier->supplier_number,
            'code' => $supplier->code,
            'name' => $supplier->name ?? $supplier->display_name,
            'display_name' => $supplier->display_name,
        ];
    }

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

    private function uomSummary(mixed $uom): ?array
    {
        if ($uom === null) {
            return null;
        }

        return [
            'id' => (int) $uom->getKey(),
            'code' => $uom->code,
            'name' => $uom->name,
            'symbol' => $uom->symbol ?? null,
            'decimal_precision' => $uom->decimal_precision ?? 6,
        ];
    }

    private function namedSummary(mixed $model): ?array
    {
        if ($model === null) {
            return null;
        }

        return [
            'id' => (int) $model->getKey(),
            'code' => $model->code ?? null,
            'name' => $model->name ?? $model->display_name ?? null,
        ];
    }
}
