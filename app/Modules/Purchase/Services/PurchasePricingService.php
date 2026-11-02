<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Supplier\Models\SupplierItemMapping;

final class PurchasePricingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly ItemPriceResolutionService $prices,
    ) {}

    /**
     * @param  list<array<string, mixed>>|null  $allowedUnits
     * @return array<string, mixed>
     */
    public function resolve(
        int $tenantId,
        ?int $organizationUnitId,
        Item $item,
        ?int $supplierId,
        ?int $variantId,
        ?int $currencyId,
        ?int $uomId,
        string $purchaseDate,
        ?array $allowedUnits = null,
    ): array {
        $purchaseDate = CarbonImmutable::parse($purchaseDate)->toDateString();
        $allowedUnits ??= $this->allowedPurchaseUnits($item, $organizationUnitId);
        $mapping = $supplierId === null
            ? null
            : $this->supplierMapping($tenantId, $organizationUnitId, $supplierId, (int) $item->getKey(), $variantId);
        $resolvedUomId = $uomId ?? $this->defaultPurchaseUomId($item, $allowedUnits, $mapping);
        if ($resolvedUomId === null) {
            throw new InvalidArgumentException('Purchase item requires a UOM.');
        }

        $resolved = $this->prices->resolvePrice(
            $item,
            ItemPriceResolutionService::CONTEXT_PURCHASE,
            $resolvedUomId,
            $organizationUnitId,
            $currencyId,
            $purchaseDate,
            $variantId,
        );

        $source = 'manual';
        $label = 'Manual price required';
        $amount = $resolved->amount;
        $priceSourceId = $resolved->priceId;
        $effectiveDate = $purchaseDate;
        if ($amount !== null) {
            $source = 'purchase_price_list';
            $label = 'Purchase price list';
        } else {
            $last = $this->lastPurchasePrice($item, $supplierId, $variantId, $currencyId, $resolvedUomId, $purchaseDate);
            if ($last instanceof PurchaseOrderLine) {
                $amount = $this->math->normalize((string) $last->unit_price);
                $source = 'last_purchase_price';
                $priceSourceId = (int) $last->getKey();
                $effectiveDate = $last->purchase_order_date === null
                    ? $purchaseDate
                    : CarbonImmutable::parse((string) $last->purchase_order_date)->toDateString();
                $label = 'Last purchased on '.CarbonImmutable::parse($effectiveDate)->format('d M Y');
            }
        }

        $context = [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_id' => $supplierId,
            'item_id' => (int) $item->getKey(),
            'item_variant_id' => $variantId,
            'uom_id' => $resolvedUomId,
            'currency_id' => $currencyId,
            'purchase_date' => $purchaseDate,
            'price_source' => $source,
            'price_source_id' => $priceSourceId,
            'effective_date' => $effectiveDate,
        ];

        return [
            'amount' => $amount === null ? null : $this->math->normalize($amount),
            'source' => $source,
            'label' => $label,
            'price_source_id' => $priceSourceId,
            'effective_date' => $effectiveDate,
            'currency_id' => $resolved->currencyId ?? $currencyId,
            'uom_id' => $resolvedUomId,
            'pricing_context_hash' => $this->contextHash($context),
            'supplier_mapping' => $mapping,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function contextHash(array $context): string
    {
        ksort($context);

        return hash('sha256', json_encode($context, JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allowedPurchaseUnits(Item $item, ?int $organizationUnitId): array
    {
        $item->loadMissing('units.uom');

        return $item->units
            ->filter(fn (ItemUnit $unit): bool => (bool) $unit->is_active
                && ($unit->organization_unit_id === null || ($organizationUnitId !== null && (int) $unit->organization_unit_id === $organizationUnitId)))
            ->map(fn (ItemUnit $unit): array => [
                'id' => (int) $unit->uom_id,
                'unit_role' => $unit->unit_role instanceof ItemUnitRole ? $unit->unit_role->value : (string) $unit->unit_role,
                'is_default' => (bool) $unit->is_default,
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
            if (($unit['unit_role'] ?? null) === ItemUnitRole::Purchase->value && (bool) ($unit['is_default'] ?? false)) {
                return (int) $unit['id'];
            }
        }

        foreach ($allowedUnits as $unit) {
            if ((bool) ($unit['is_default'] ?? false)) {
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

    private function lastPurchasePrice(
        Item $item,
        ?int $supplierId,
        ?int $variantId,
        ?int $currencyId,
        int $uomId,
        string $purchaseDate,
    ): ?PurchaseOrderLine {
        return PurchaseOrderLine::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->where('purchase_order_lines.tenant_id', $item->tenant_id)
            ->where('purchase_order_lines.item_id', $item->getKey())
            ->where('purchase_order_lines.uom_id', $uomId)
            ->where('purchase_orders.purchase_order_date', '<=', $purchaseDate)
            ->when($supplierId !== null, fn (Builder $query) => $query->where('purchase_orders.supplier_id', $supplierId))
            ->when($variantId === null, fn (Builder $query) => $query->whereNull('purchase_order_lines.item_variant_id'), fn (Builder $query) => $query->where('purchase_order_lines.item_variant_id', $variantId))
            ->when($currencyId === null, fn (Builder $query) => $query->whereNull('purchase_orders.currency_id'), fn (Builder $query) => $query->where('purchase_orders.currency_id', $currencyId))
            ->whereNotIn('purchase_orders.status', [
                PurchaseOrderStatus::Draft->value,
                PurchaseOrderStatus::Cancelled->value,
            ])
            ->orderByDesc('purchase_orders.purchase_order_date')
            ->orderByDesc('purchase_order_lines.id')
            ->first([
                'purchase_order_lines.id',
                'purchase_order_lines.unit_price',
                'purchase_orders.purchase_order_date',
            ]);
    }
}
