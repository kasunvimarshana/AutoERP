<?php

declare(strict_types=1);

namespace Modules\Purchase\Validators;

use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class PurchaseValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function assertPositiveQuantity(string $quantity): void
    {
        if ($this->math->compare($quantity, '0.000000') <= 0) {
            throw new InvalidArgumentException('Purchase quantity must be greater than zero.');
        }
    }

    public function assertNonNegative(string $amount, string $message = 'Purchase amount cannot be negative.'): void
    {
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException($message);
        }
    }

    public function item(int $tenantId, ?int $organizationUnitId, int $itemId, string $field = 'item_id'): Item
    {
        $item = Item::query()->find($itemId);
        if (! $item instanceof Item) {
            $this->invalidReference($field, 'item');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($item->tenant_id),
            $this->nullableScopeId($item->organization_unit_id),
            $tenantId,
            $organizationUnitId,
            $field,
            'item',
        );

        if (! (bool) $item->is_active) {
            $this->invalidReference($field, 'item', 'The selected item is not active.');
        }

        return $item;
    }

    public function supplier(int $tenantId, ?int $organizationUnitId, int $supplierId, string $field = 'supplier_id'): Supplier
    {
        $supplier = Supplier::query()->find($supplierId);
        if (! $supplier instanceof Supplier) {
            $this->invalidReference($field, 'supplier');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($supplier->tenant_id),
            $this->nullableScopeId($supplier->organization_unit_id),
            $tenantId,
            $organizationUnitId,
            $field,
            'supplier',
        );

        $status = $supplier->status instanceof \BackedEnum
            ? $supplier->status->value
            : (string) $supplier->status;
        if ($status !== 'active') {
            $this->invalidReference($field, 'supplier', 'The selected supplier is not active.');
        }

        return $supplier;
    }

    public function uom(int $tenantId, ?int $organizationUnitId, int $uomId, string $field = 'uom_id'): UnitOfMeasureModel
    {
        $uom = UnitOfMeasureModel::query()->find($uomId);
        if (! $uom instanceof UnitOfMeasureModel) {
            $this->invalidReference($field, 'UOM');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($uom->tenant_id),
            $this->nullableScopeId($uom->organization_unit_id),
            $tenantId,
            $organizationUnitId,
            $field,
            'UOM',
        );

        if (isset($uom->is_active) && ! (bool) $uom->is_active) {
            $this->invalidReference($field, 'UOM', 'The selected UOM is not active.');
        }

        return $uom;
    }

    public function itemVariant(int $tenantId, ?int $organizationUnitId, int $itemId, int $variantId, string $field = 'item_variant_id'): ItemVariant
    {
        $variant = ItemVariant::query()->find($variantId);
        if (! $variant instanceof ItemVariant) {
            $this->invalidReference($field, 'item variant');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($variant->tenant_id),
            $this->nullableScopeId($variant->organization_unit_id),
            $tenantId,
            $organizationUnitId,
            $field,
            'item variant',
        );

        if ((int) $variant->item_id !== $itemId) {
            $this->invalidReference($field, 'item variant', 'The selected item variant does not belong to the selected item.');
        }

        if (isset($variant->is_active) && ! (bool) $variant->is_active) {
            $this->invalidReference($field, 'item variant', 'The selected item variant is not active.');
        }

        return $variant;
    }

    public function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId, string $field = 'warehouse_id'): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->find($warehouseId);
        if (! $warehouse instanceof WarehouseModel) {
            $this->invalidReference($field, 'warehouse');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($warehouse->tenant_id),
            $this->nullableScopeId($warehouse->organization_unit_id ?? null),
            $tenantId,
            $organizationUnitId,
            $field,
            'warehouse',
        );

        if (isset($warehouse->is_active) && ! (bool) $warehouse->is_active) {
            $this->invalidReference($field, 'warehouse', 'The selected warehouse is not active.');
        }

        return $warehouse;
    }

    public function warehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, int $locationId, string $field = 'warehouse_location_id'): WarehouseLocationModel
    {
        $location = WarehouseLocationModel::query()->find($locationId);
        if (! $location instanceof WarehouseLocationModel) {
            $this->invalidReference($field, 'warehouse location');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($location->tenant_id),
            $this->nullableScopeId($location->organization_unit_id ?? null),
            $tenantId,
            $organizationUnitId,
            $field,
            'warehouse location',
        );

        if ((int) $location->warehouse_id !== $warehouseId) {
            $this->invalidReference($field, 'warehouse location', 'The selected warehouse location does not belong to the selected warehouse.');
        }

        $warehouse = WarehouseModel::query()->find($warehouseId);
        if (! $warehouse instanceof WarehouseModel || (int) $location->tenant_id !== (int) $warehouse->tenant_id) {
            $this->invalidReference($field, 'warehouse location', 'The selected warehouse location does not match the selected warehouse scope.');
        }

        if (isset($location->is_active) && ! (bool) $location->is_active) {
            $this->invalidReference($field, 'warehouse location', 'The selected warehouse location is not active.');
        }

        return $location;
    }

    public function currency(int $tenantId, ?int $organizationUnitId, int $currencyId, string $field = 'currency_id'): CurrencyModel
    {
        $currency = CurrencyModel::query()->find($currencyId);
        if (! $currency instanceof CurrencyModel) {
            $this->invalidReference($field, 'currency');
        }

        if (isset($currency->is_active) && ! (bool) $currency->is_active) {
            $this->invalidReference($field, 'currency', 'The selected currency is not active.');
        }

        return $currency;
    }

    public function taxGroup(int $tenantId, ?int $organizationUnitId, int $taxGroupId, string $field = 'tax_group_id'): TaxGroup
    {
        $group = TaxGroup::query()->find($taxGroupId);
        if (! $group instanceof TaxGroup) {
            $this->invalidReference($field, 'tax group');
        }

        $this->assertTenantOrg(
            $this->nullableScopeId($group->tenant_id),
            $this->nullableScopeId($group->organization_unit_id),
            $tenantId,
            $organizationUnitId,
            $field,
            'tax group',
        );

        if (! (bool) $group->active) {
            $this->invalidReference($field, 'tax group', 'The selected tax group is not active.');
        }

        return $group;
    }

    public function assertTenantOrg(
        ?int $actualTenantId,
        ?int $actualOrgId,
        int $tenantId,
        ?int $organizationUnitId,
        ?string $field = null,
        string $label = 'reference',
    ): void
    {
        if ($actualTenantId !== null && $actualTenantId !== $tenantId) {
            if ($field !== null) {
                $this->invalidReference($field, $label);
            }

            throw new InvalidArgumentException('Purchase reference belongs to a different tenant.');
        }

        if ($actualOrgId !== null && $actualOrgId !== $organizationUnitId) {
            if ($field !== null) {
                $this->invalidReference($field, $label, "The selected {$label} is not available for this organization unit.");
            }

            throw new InvalidArgumentException('Purchase reference belongs to a different organization unit.');
        }
    }

    public function invalidReference(string $field, string $label, ?string $message = null): never
    {
        throw ValidationException::withMessages([
            $field => [$message ?? "The selected {$label} is not available."],
        ]);
    }

    private function nullableScopeId(mixed $value): ?int
    {
        return $value !== null && $value !== '' ? (int) $value : null;
    }

    public function assertReceiptWithinOrder(PurchaseOrderLine $line, string $receivedQuantity): void
    {
        $remaining = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $remaining = $this->math->sub($remaining, (string) $line->cancelled_quantity);

        if ($this->math->compare($receivedQuantity, $remaining) > 0) {
            throw new InvalidArgumentException('Received quantity cannot exceed purchase order remaining quantity.');
        }
    }

    public function assertReturnWithinReceipt(GoodsReceiptNoteLine $line, string $returnedQuantity): void
    {
        $returnable = $this->math->sub((string) $line->accepted_quantity, (string) $line->returned_quantity);
        if ($this->math->compare($returnedQuantity, $returnable) > 0) {
            throw new InvalidArgumentException('Returned quantity cannot exceed received remaining quantity.');
        }
    }
}
