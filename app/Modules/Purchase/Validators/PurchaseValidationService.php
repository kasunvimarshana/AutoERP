<?php

declare(strict_types=1);

namespace Modules\Purchase\Validators;

use InvalidArgumentException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Supplier\Models\Supplier;
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

    public function item(int $tenantId, ?int $organizationUnitId, int $itemId): Item
    {
        $item = Item::query()->findOrFail($itemId);
        $this->assertTenantOrg((int) $item->tenant_id, $item->organization_unit_id, $tenantId, $organizationUnitId);

        if (! (bool) $item->is_active) {
            throw new InvalidArgumentException('Purchase item must be active.');
        }

        return $item;
    }

    public function supplier(int $tenantId, ?int $organizationUnitId, int $supplierId): Supplier
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $this->assertTenantOrg((int) $supplier->tenant_id, $supplier->organization_unit_id, $tenantId, $organizationUnitId);

        $status = $supplier->status instanceof \BackedEnum
            ? $supplier->status->value
            : (string) $supplier->status;
        if ($status !== 'active') {
            throw new InvalidArgumentException('Purchase supplier must be active.');
        }

        return $supplier;
    }

    public function uom(int $tenantId, ?int $organizationUnitId, int $uomId): UnitOfMeasureModel
    {
        $uom = UnitOfMeasureModel::query()->findOrFail($uomId);
        $this->assertTenantOrg((int) $uom->tenant_id, $uom->organization_unit_id, $tenantId, $organizationUnitId);

        if (isset($uom->is_active) && ! (bool) $uom->is_active) {
            throw new InvalidArgumentException('Purchase UOM must be active.');
        }

        return $uom;
    }

    public function itemVariant(int $tenantId, ?int $organizationUnitId, int $itemId, int $variantId): ItemVariant
    {
        $variant = ItemVariant::query()->findOrFail($variantId);
        $this->assertTenantOrg((int) $variant->tenant_id, $variant->organization_unit_id, $tenantId, $organizationUnitId);

        if ((int) $variant->item_id !== $itemId) {
            throw new InvalidArgumentException('Purchase item variant must belong to the selected item.');
        }

        if (isset($variant->is_active) && ! (bool) $variant->is_active) {
            throw new InvalidArgumentException('Purchase item variant must be active.');
        }

        return $variant;
    }

    public function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->findOrFail($warehouseId);
        $this->assertTenantOrg((int) $warehouse->tenant_id, $warehouse->organization_unit_id ?? null, $tenantId, $organizationUnitId);

        if (isset($warehouse->is_active) && ! (bool) $warehouse->is_active) {
            throw new InvalidArgumentException('Purchase warehouse must be active.');
        }

        return $warehouse;
    }

    public function warehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, int $locationId): WarehouseLocationModel
    {
        $location = WarehouseLocationModel::query()->findOrFail($locationId);
        $this->assertTenantOrg((int) $location->tenant_id, $location->organization_unit_id ?? null, $tenantId, $organizationUnitId);

        if ((int) $location->warehouse_id !== $warehouseId) {
            throw new InvalidArgumentException('Purchase warehouse location must belong to the selected warehouse.');
        }

        if (isset($location->is_active) && ! (bool) $location->is_active) {
            throw new InvalidArgumentException('Purchase warehouse location must be active.');
        }

        return $location;
    }

    public function currency(int $tenantId, ?int $organizationUnitId, int $currencyId): CurrencyModel
    {
        $currency = CurrencyModel::query()->findOrFail($currencyId);
        $this->assertTenantOrg((int) $currency->tenant_id, $currency->organization_unit_id ?? null, $tenantId, $organizationUnitId);

        if (isset($currency->is_active) && ! (bool) $currency->is_active) {
            throw new InvalidArgumentException('Purchase currency must be active.');
        }

        return $currency;
    }

    public function assertTenantOrg(?int $actualTenantId, ?int $actualOrgId, int $tenantId, ?int $organizationUnitId): void
    {
        if ($actualTenantId !== null && $actualTenantId !== $tenantId) {
            throw new InvalidArgumentException('Purchase reference belongs to a different tenant.');
        }

        if ($organizationUnitId !== null && $actualOrgId !== null && $actualOrgId !== $organizationUnitId) {
            throw new InvalidArgumentException('Purchase reference belongs to a different organization unit.');
        }
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
