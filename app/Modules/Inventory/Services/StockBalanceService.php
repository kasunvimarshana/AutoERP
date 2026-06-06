<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Models\InventoryStockBalance;

final class StockBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function getOrCreate(StockBalanceData $data): InventoryStockBalance
    {
        /** @var InventoryStockBalance $balance */
        $balance = InventoryStockBalance::query()->firstOrCreate(
            [
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'item_id' => $data->itemId,
                'item_variant_id' => $data->itemVariantId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'batch_id' => $data->batchId,
            ],
            [
                'quantity_on_hand' => '0.000000',
                'quantity_reserved' => '0.000000',
                'quantity_allocated' => '0.000000',
                'quantity_available' => '0.000000',
                'average_cost' => '0.000000',
                'total_value' => '0.000000',
            ],
        );

        return $balance;
    }

    public function increase(InventoryStockBalance $balance, string $quantity, string $unitCost): InventoryStockBalance
    {
        $oldQty = (string) $balance->quantity_on_hand;
        $oldValue = (string) $balance->total_value;
        $movementValue = $this->math->mul($quantity, $unitCost);
        $newQty = $this->math->add($oldQty, $quantity);
        $newValue = $this->math->add($oldValue, $movementValue);

        $balance->quantity_on_hand = $newQty;
        $balance->total_value = $newValue;
        $balance->average_cost = $this->math->isZero($newQty) ? '0.000000' : $this->math->div($newValue, $newQty);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function decrease(InventoryStockBalance $balance, string $quantity, string $unitCost): InventoryStockBalance
    {
        $newQty = $this->math->sub((string) $balance->quantity_on_hand, $quantity);
        $newValue = $this->math->sub((string) $balance->total_value, $this->math->mul($quantity, $unitCost));
        if ($this->math->isNegative($newValue)) {
            $newValue = '0.000000';
        }

        $balance->quantity_on_hand = $newQty;
        $balance->total_value = $this->math->isZero($newQty) ? '0.000000' : $newValue;
        $balance->average_cost = $this->math->isZero($newQty) ? '0.000000' : $this->math->div((string) $balance->total_value, $newQty);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function reserve(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        $balance->quantity_reserved = $this->math->add((string) $balance->quantity_reserved, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function releaseReserved(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        $balance->quantity_reserved = $this->math->sub((string) $balance->quantity_reserved, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function allocate(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        $balance->quantity_allocated = $this->math->add((string) $balance->quantity_allocated, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function releaseAllocated(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        $balance->quantity_allocated = $this->math->sub((string) $balance->quantity_allocated, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function recalculateAvailable(InventoryStockBalance $balance): void
    {
        $balance->quantity_available = $this->math->sub(
            $this->math->sub((string) $balance->quantity_on_hand, (string) $balance->quantity_reserved),
            (string) $balance->quantity_allocated,
        );
    }
}
