<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Item\Models\Item;

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

    public function getOrCreateForUpdate(StockBalanceData $data): InventoryStockBalance
    {
        Item::query()
            ->where('tenant_id', $data->tenantId)
            ->whereKey($data->itemId)
            ->lockForUpdate()
            ->firstOrFail();

        $balance = $this->balanceQuery($data)->lockForUpdate()->first();
        if (! $balance instanceof InventoryStockBalance) {
            $this->getOrCreate($data);
            $balance = $this->balanceQuery($data)->lockForUpdate()->firstOrFail();
        }

        return $balance;
    }

    public function lockById(int $balanceId): InventoryStockBalance
    {
        return InventoryStockBalance::query()->lockForUpdate()->findOrFail($balanceId);
    }

    public function increase(InventoryStockBalance $balance, string $quantity, string $unitCost): InventoryStockBalance
    {
        return $this->increaseByValue($balance, $quantity, $this->math->mul($quantity, $unitCost));
    }

    public function increaseByValue(InventoryStockBalance $balance, string $quantity, string $movementValue): InventoryStockBalance
    {
        $oldQty = (string) $balance->quantity_on_hand;
        $oldValue = (string) $balance->total_value;
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
        return $this->decreaseByValue($balance, $quantity, $this->math->mul($quantity, $unitCost));
    }

    public function decreaseByValue(InventoryStockBalance $balance, string $quantity, string $movementValue): InventoryStockBalance
    {
        if ($this->math->compare((string) $balance->quantity_on_hand, $quantity) < 0
            || $this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory issue quantity cannot exceed available stock.');
        }

        $newQty = $this->math->sub((string) $balance->quantity_on_hand, $quantity);
        $newValue = $this->math->sub((string) $balance->total_value, $movementValue);
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
        if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory reservation quantity cannot exceed available stock.');
        }

        $balance->quantity_reserved = $this->math->add((string) $balance->quantity_reserved, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function releaseReserved(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        if ($this->math->compare((string) $balance->quantity_reserved, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory reserved quantity cannot become negative.');
        }

        $balance->quantity_reserved = $this->math->sub((string) $balance->quantity_reserved, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function allocate(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory allocation cannot exceed available stock.');
        }

        $balance->quantity_allocated = $this->math->add((string) $balance->quantity_allocated, $quantity);
        $this->recalculateAvailable($balance);
        $balance->save();

        return $balance->refresh();
    }

    public function releaseAllocated(InventoryStockBalance $balance, string $quantity): InventoryStockBalance
    {
        if ($this->math->compare((string) $balance->quantity_allocated, $quantity) < 0) {
            throw new InvalidArgumentException('Inventory allocated quantity cannot become negative.');
        }

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
        if ($this->math->isNegative((string) $balance->quantity_available)) {
            throw new InvalidArgumentException('Inventory available quantity cannot become negative.');
        }
    }

    private function balanceQuery(StockBalanceData $data)
    {
        $query = InventoryStockBalance::query()
            ->where('tenant_id', $data->tenantId)
            ->where('item_id', $data->itemId)
            ->where('warehouse_id', $data->warehouseId);
        foreach ([
            'organization_unit_id' => $data->organizationUnitId,
            'item_variant_id' => $data->itemVariantId,
            'warehouse_location_id' => $data->warehouseLocationId,
            'batch_id' => $data->batchId,
        ] as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }
}
