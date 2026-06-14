<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\InventoryStockState;
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
        private readonly InventoryUomService $uoms,
        private readonly InventoryStockStateService $states,
    ) {}

    public function reserve(ReservationData $data): InventoryReservation
    {
        $quantity = $this->math->normalize($data->quantityReserved);
        $this->validator->assertPositiveQuantity($quantity);
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $basis = $this->uoms->basis(
            $data->tenantId,
            $data->organizationUnitId,
            $item,
            $data->uomId,
            $quantity,
        );
        $quantity = $basis->baseQuantity;
        $item = $item->refresh();
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        $this->validator->batch($item, $data->batchId, $data->itemVariantId);

        return DB::transaction(function () use ($basis, $data, $quantity): InventoryReservation {
            $balance = $this->balances->getOrCreateForUpdate($this->balanceData($data));
            $this->balances->reserve($balance, $quantity);

            $reservation = InventoryReservation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'reservation_number' => $data->reservationNumber ?? $this->numbers->next($data->tenantId, 'RES'),
                'reservation_date' => $data->reservationDate,
                'item_id' => $data->itemId,
                'base_uom_id' => $basis->baseUomId,
                'entered_uom_id' => $basis->enteredUomId,
                'item_variant_id' => $data->itemVariantId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'batch_id' => $data->batchId,
                'entered_quantity' => $basis->enteredQuantity,
                'conversion_factor' => $basis->conversionFactor,
                'quantity_reserved' => $quantity,
                'quantity_remaining' => $quantity,
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'source_line_type' => $data->sourceLineType,
                'source_line_id' => $data->sourceLineId,
                'status' => ReservationStatus::Active,
                'expires_at' => $data->expiresAt,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);
            $this->states->record(
                $balance,
                InventoryStockState::Available,
                InventoryStockState::Reserved,
                $quantity,
                $data->sourceType ?? 'inventory_reservation',
                $data->sourceId ?? (int) $reservation->getKey(),
                $data->sourceLineType,
                $data->sourceLineId,
                'Inventory reservation '.$reservation->reservation_number,
                $data->createdBy,
            );

            return $reservation;
        });
    }

    public function release(InventoryReservation $reservation, ?string $quantity = null, ?int $releasedBy = null): InventoryReservation
    {
        return DB::transaction(function () use ($reservation, $quantity, $releasedBy): InventoryReservation {
            $reservation = InventoryReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            if (! in_array($reservation->status, [ReservationStatus::Active, ReservationStatus::PartiallyAllocated], true)) {
                throw new InvalidArgumentException('Only active inventory reservations can be released.');
            }

            $releaseQty = $this->math->normalize($quantity ?? (string) $reservation->quantity_remaining);
            $this->validator->assertPositiveQuantity($releaseQty);
            if ($this->math->compare($releaseQty, (string) $reservation->quantity_remaining) > 0) {
                throw new InvalidArgumentException('Inventory reservation release cannot exceed remaining quantity.');
            }

            $balance = $this->balances->getOrCreateForUpdate($this->balanceDataFromReservation($reservation));
            $this->balances->releaseReserved($balance, $releaseQty);

            $reservation->quantity_released = $this->math->add((string) $reservation->quantity_released, $releaseQty);
            $reservation->quantity_remaining = $this->math->sub((string) $reservation->quantity_remaining, $releaseQty);
            if ($this->math->isZero((string) $reservation->quantity_remaining)) {
                $reservation->status = ReservationStatus::Released;
                $reservation->released_by = $releasedBy;
                $reservation->released_at = now();
            }
            $reservation->save();
            $this->states->record(
                $balance,
                InventoryStockState::Reserved,
                InventoryStockState::Available,
                $releaseQty,
                $reservation->source_type ?? 'inventory_reservation',
                $reservation->source_id ?? (int) $reservation->getKey(),
                $reservation->source_line_type,
                $reservation->source_line_id,
                'Inventory reservation release '.$reservation->reservation_number,
                $releasedBy,
            );

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
