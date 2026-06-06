<?php

declare(strict_types=1);

namespace Modules\Inventory\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Enums\BatchStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function assertPositiveQuantity(string $quantity): void
    {
        if ($this->math->isNegative($quantity) || $this->math->isZero($quantity)) {
            throw new InvalidArgumentException('Inventory quantity must be greater than zero.');
        }
    }

    public function assertNonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    public function item(int $tenantId, ?int $organizationUnitId, int $itemId): Item
    {
        $item = Item::query()->findOrFail($itemId);
        $this->assertScope($tenantId, $organizationUnitId, (int) $item->tenant_id, $item->organization_unit_id);

        if (! (bool) $item->is_active) {
            throw new InvalidArgumentException('Inactive item cannot be used for inventory.');
        }

        return $item;
    }

    public function assertStockable(Item $item): void
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);
        if (! (bool) $item->is_stockable || in_array($type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true)) {
            throw new InvalidArgumentException('Only stockable items can affect inventory balances.');
        }
    }

    public function variant(Item $item, ?int $variantId): ?ItemVariant
    {
        if ($variantId === null) {
            return null;
        }

        $variant = ItemVariant::query()->findOrFail($variantId);
        if ((int) $variant->item_id !== (int) $item->getKey()) {
            throw new InvalidArgumentException('Inventory item variant must belong to the item.');
        }

        return $variant;
    }

    public function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->findOrFail($warehouseId);
        $this->assertScope($tenantId, $organizationUnitId, (int) $warehouse->tenant_id, $warehouse->organization_unit_id);

        if (! (bool) $warehouse->is_active) {
            throw new InvalidArgumentException('Inactive warehouse cannot be used for inventory.');
        }

        return $warehouse;
    }

    public function location(WarehouseModel $warehouse, ?int $locationId): ?WarehouseLocationModel
    {
        if ($locationId === null) {
            return null;
        }

        $location = WarehouseLocationModel::query()->findOrFail($locationId);
        if ((int) $location->warehouse_id !== (int) $warehouse->getKey()) {
            throw new InvalidArgumentException('Warehouse location must belong to the warehouse.');
        }

        if (! (bool) $location->is_active) {
            throw new InvalidArgumentException('Inactive warehouse location cannot be used for inventory.');
        }

        return $location;
    }

    public function batch(Item $item, ?int $batchId): ?InventoryBatch
    {
        $tracking = $item->tracking_type instanceof TrackingType ? $item->tracking_type : TrackingType::from((string) $item->tracking_type);
        if (in_array($tracking, [TrackingType::Batch, TrackingType::Lot], true) && $batchId === null) {
            throw new InvalidArgumentException('Batch or lot tracked items require a batch reference.');
        }

        if ($batchId === null) {
            return null;
        }

        $batch = InventoryBatch::query()->findOrFail($batchId);
        if ((int) $batch->item_id !== (int) $item->getKey()) {
            throw new InvalidArgumentException('Inventory batch must belong to the item.');
        }

        if ($batch->status !== BatchStatus::Active) {
            throw new InvalidArgumentException('Only active inventory batches can be used.');
        }

        return $batch;
    }

    public function serial(Item $item, ?int $serialId, string $quantity): ?InventorySerialNumber
    {
        $tracking = $item->tracking_type instanceof TrackingType ? $item->tracking_type : TrackingType::from((string) $item->tracking_type);
        if ($tracking === TrackingType::Serial) {
            if ($serialId === null) {
                throw new InvalidArgumentException('Serial tracked items require a serial number reference.');
            }

            if ($this->math->compare($quantity, '1.000000') !== 0) {
                throw new InvalidArgumentException('Serial tracked inventory movement quantity must be 1.');
            }
        }

        if ($serialId === null) {
            return null;
        }

        $serial = InventorySerialNumber::query()->findOrFail($serialId);
        if ((int) $serial->item_id !== (int) $item->getKey()) {
            throw new InvalidArgumentException('Inventory serial number must belong to the item.');
        }

        if ($serial->status === SerialStatus::Blocked || $serial->status === SerialStatus::Damaged) {
            throw new InvalidArgumentException('Blocked or damaged serial numbers cannot be used.');
        }

        return $serial;
    }

    public function assertScope(int $tenantId, ?int $organizationUnitId, int $recordTenantId, ?int $recordOrganizationUnitId): void
    {
        if ($recordTenantId !== $tenantId) {
            throw new InvalidArgumentException('Inventory reference belongs to a different tenant.');
        }

        if ($organizationUnitId !== null && $recordOrganizationUnitId !== null && (int) $recordOrganizationUnitId !== $organizationUnitId) {
            throw new InvalidArgumentException('Inventory reference belongs to a different organization unit.');
        }
    }
}
