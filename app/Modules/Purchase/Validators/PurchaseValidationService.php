<?php

declare(strict_types=1);

namespace Modules\Purchase\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Warehouse\Models\WarehouseModel;

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

    public function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->findOrFail($warehouseId);
        $this->assertTenantOrg((int) $warehouse->tenant_id, $warehouse->organization_unit_id ?? null, $tenantId, $organizationUnitId);

        if (isset($warehouse->is_active) && ! (bool) $warehouse->is_active) {
            throw new InvalidArgumentException('Purchase warehouse must be active.');
        }

        return $warehouse;
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
