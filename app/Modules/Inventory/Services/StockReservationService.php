<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockReservationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockBalanceService $balances,
    ) {}

    public function reserve(ReservationData $data): InventoryReservation
    {
        $quantity = $this->math->normalize($data->quantityReserved);
        $this->validator->assertPositiveQuantity($quantity);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId);

        return DB::transaction(function () use ($data, $quantity): InventoryReservation {
            $balance = $this->balances->getOrCreate($this->balanceData($data));
            if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                throw new InvalidArgumentException('Inventory reservation quantity cannot exceed available stock.');
            }

            $this->balances->reserve($balance, $quantity);

            return InventoryReservation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'reservation_number' => $data->reservationNumber ?? $this->numbers->next($data->tenantId, 'RES', 'inventory_reservations', 'reservation_number'),
                'reservation_date' => $data->reservationDate,
                'item_id' => $data->itemId,
                'item_variant_id' => $data->itemVariantId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'batch_id' => $data->batchId,
                'quantity_reserved' => $quantity,
                'quantity_remaining' => $quantity,
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'source_line_type' => $data->sourceLineType,
                'source_line_id' => $data->sourceLineId,
                'status' => ReservationStatus::Active,
                'expires_at' => $data->expiresAt,
                'notes' => $data->notes,
            ]);
        });
    }

    public function release(InventoryReservation $reservation, ?string $quantity = null): InventoryReservation
    {
        if (! in_array($reservation->status, [ReservationStatus::Active, ReservationStatus::PartiallyAllocated], true)) {
            throw new InvalidArgumentException('Only active inventory reservations can be released.');
        }

        $releaseQty = $this->math->normalize($quantity ?? (string) $reservation->quantity_remaining);
        if ($this->math->compare($releaseQty, (string) $reservation->quantity_remaining) > 0) {
            throw new InvalidArgumentException('Inventory reservation release cannot exceed remaining quantity.');
        }

        return DB::transaction(function () use ($reservation, $releaseQty): InventoryReservation {
            $balance = $this->balances->getOrCreate($this->balanceDataFromReservation($reservation));
            $this->balances->releaseReserved($balance, $releaseQty);

            $reservation->quantity_released = $this->math->add((string) $reservation->quantity_released, $releaseQty);
            $reservation->quantity_remaining = $this->math->sub((string) $reservation->quantity_remaining, $releaseQty);
            if ($this->math->isZero((string) $reservation->quantity_remaining)) {
                $reservation->status = ReservationStatus::Released;
            }
            $reservation->save();

            return $reservation->refresh();
        });
    }

    private function balanceData(ReservationData $data): StockBalanceData
    {
        return new StockBalanceData($data->tenantId, $data->itemId, $data->warehouseId, $data->organizationUnitId, $data->itemVariantId, $data->warehouseLocationId, $data->batchId);
    }

    private function balanceDataFromReservation(InventoryReservation $reservation): StockBalanceData
    {
        return new StockBalanceData((int) $reservation->tenant_id, (int) $reservation->item_id, (int) $reservation->warehouse_id, $reservation->organization_unit_id, $reservation->item_variant_id, $reservation->warehouse_location_id, $reservation->batch_id);
    }
}
