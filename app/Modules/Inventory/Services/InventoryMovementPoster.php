<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySerialNumber;

final class InventoryMovementPoster
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockBalanceService $balances,
        private readonly InventoryValuationService $valuation,
        private readonly InventoryStockStateService $states,
    ) {}

    public function post(InventoryMovement $movement, ?int $postedBy = null): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $postedBy): InventoryMovement {
            $movement = InventoryMovement::query()
                ->with('item.category')
                ->lockForUpdate()
                ->findOrFail($movement->getKey());
            if ($movement->status !== InventoryStatus::Draft) {
                throw new InvalidArgumentException('Only draft inventory movements can be posted.');
            }

            $balance = $this->balances->getOrCreateForUpdate($this->balanceData($movement));
            $quantity = (string) $movement->quantity;

            $subsequentMovements = $this->subsequentMovements($movement);
            $priorMovement = $this->priorMovement($movement);
            $priorBalanceQty = (string) ($priorMovement?->balance_quantity_after ?? '0.000000');
            $priorBalanceVal = (string) ($priorMovement?->balance_value_after ?? '0.000000');

            if ($movement->direction === InventoryDirection::Out) {
                if ($subsequentMovements->isEmpty()) {
                    if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                        throw new InvalidArgumentException('Inventory issue quantity cannot exceed available stock.');
                    }
                } else {
                    if ($this->math->compare($priorBalanceQty, $quantity) < 0) {
                        throw new InvalidArgumentException('Inventory issue quantity cannot exceed available stock.');
                    }
                    if ($this->math->compare((string) $balance->quantity_available, $quantity) < 0) {
                        throw new InvalidArgumentException(
                            'Inventory issue would cause negative stock in subsequent periods.',
                        );
                    }
                    foreach ($subsequentMovements as $subsequent) {
                        if ($this->math->compare((string) $subsequent->balance_quantity_after, $quantity) < 0) {
                            throw new InvalidArgumentException(
                                'Inventory issue would cause negative stock in subsequent periods.',
                            );
                        }
                    }
                }
            }

            $valuation = $movement->reversal_of_id === null
                ? ($movement->direction === InventoryDirection::In
                    ? $this->valuation->receive($movement)
                    : $this->valuation->issue($movement, $quantity))
                : $this->valuation->reverse(
                    InventoryMovement::query()->lockForUpdate()->findOrFail($movement->reversal_of_id),
                    $movement,
                );

            $movement->unit_cost = $valuation->unitCost;
            $movement->total_cost = $valuation->totalCost;
            if ($movement->direction === InventoryDirection::In) {
                $this->balances->increaseByValue($balance, $quantity, $valuation->totalCost);
            } else {
                $this->balances->decreaseByValue($balance, $quantity, $valuation->totalCost);
            }

            $this->updateSerial($movement);
            $balance->refresh();

            if ($subsequentMovements->isEmpty()) {
                $movement->balance_quantity_after = $balance->quantity_on_hand;
                $movement->balance_value_after = $balance->total_value;
            } else {
                if ($movement->direction === InventoryDirection::In) {
                    $movement->balance_quantity_after = $this->math->add($priorBalanceQty, $quantity);
                    $movement->balance_value_after = $this->math->add($priorBalanceVal, $valuation->totalCost);
                } else {
                    $movement->balance_quantity_after = $this->math->sub($priorBalanceQty, $quantity);
                    $movement->balance_value_after = $this->math->sub($priorBalanceVal, $valuation->totalCost);
                }

                foreach ($subsequentMovements as $subsequent) {
                    if ($movement->direction === InventoryDirection::In) {
                        $subsequent->balance_quantity_after = $this->math->add((string) $subsequent->balance_quantity_after, $quantity);
                        $subsequent->balance_value_after = $this->math->add((string) $subsequent->balance_value_after, $valuation->totalCost);
                    } else {
                        $subsequent->balance_quantity_after = $this->math->sub((string) $subsequent->balance_quantity_after, $quantity);
                        $subsequent->balance_value_after = $this->math->sub((string) $subsequent->balance_value_after, $valuation->totalCost);
                    }
                    $subsequent->save();
                }
            }

            $movement->status = InventoryStatus::Posted;
            $movement->posted_by = $postedBy;
            $movement->posted_at = now();
            $movement->save();
            $this->states->record(
                $movement,
                $movement->from_state,
                $movement->to_state,
                $quantity,
                $movement->source_type,
                $movement->source_id,
                $movement->source_line_type,
                $movement->source_line_id,
                $movement->description,
                $postedBy ?? $movement->created_by,
            );

            return $movement->refresh();
        });
    }

    private function balanceData(InventoryMovement $movement): StockBalanceData
    {
        return new StockBalanceData(
            tenantId: (int) $movement->tenant_id,
            itemId: (int) $movement->item_id,
            warehouseId: (int) $movement->warehouse_id,
            organizationUnitId: $movement->organization_unit_id,
            itemVariantId: $movement->item_variant_id,
            warehouseLocationId: $movement->warehouse_location_id,
            batchId: $movement->batch_id,
        );
    }

    private function updateSerial(InventoryMovement $movement): void
    {
        if ($movement->serial_number_id === null) {
            return;
        }

        $serial = InventorySerialNumber::query()->lockForUpdate()->findOrFail($movement->serial_number_id);
        $latestMovement = InventoryMovement::query()
            ->where('serial_number_id', $serial->getKey())
            ->where('id', '!=', $movement->getKey())
            ->where('status', InventoryStatus::Posted->value)
            ->latest('id')
            ->first();

        if ($movement->direction === InventoryDirection::In) {
            if ($serial->status === SerialStatus::Reserved
                || ($serial->status === SerialStatus::Available
                    && $latestMovement?->direction === InventoryDirection::In)) {
                throw new InvalidArgumentException('Inventory serial number is already available in stock.');
            }

            $serial->status = SerialStatus::Available;
            $serial->warehouse_id = $movement->warehouse_id;
            $serial->warehouse_location_id = $movement->warehouse_location_id;
            $serial->batch_id = $movement->batch_id;
        } else {
            if (! in_array($serial->status, [SerialStatus::Available, SerialStatus::Reserved], true)) {
                throw new InvalidArgumentException('Inventory serial number is not available for issue.');
            }
            if (! $latestMovement instanceof InventoryMovement
                || $latestMovement->direction !== InventoryDirection::In) {
                throw new InvalidArgumentException('Inventory serial number has no available receipt to issue.');
            }
            if ((int) $serial->warehouse_id !== (int) $movement->warehouse_id
                || $serial->warehouse_location_id !== $movement->warehouse_location_id
                || $serial->batch_id !== $movement->batch_id) {
                throw new InvalidArgumentException('Inventory serial number does not match the issue stock location.');
            }
            $serial->status = $movement->reversal_of_id === null ? SerialStatus::Issued : SerialStatus::Returned;
        }
        $serial->save();
    }

    private function priorMovement(InventoryMovement $movement): ?InventoryMovement
    {
        $date = $movement->movement_date->toDateString();
        $id = (int) $movement->getKey();

        return $this->dimensionQuery($movement)
            ->where('id', '!=', $id)
            ->where(function (Builder $q) use ($date, $id): void {
                $q->whereDate('movement_date', '<', $date)
                    ->orWhere(function (Builder $q2) use ($date, $id): void {
                        $q2->whereDate('movement_date', '=', $date)
                            ->where('id', '<', $id);
                    });
            })
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, InventoryMovement>
     */
    private function subsequentMovements(InventoryMovement $movement): Collection
    {
        $date = $movement->movement_date->toDateString();
        $id = (int) $movement->getKey();

        /** @var Collection<int, InventoryMovement> */
        return $this->dimensionQuery($movement)
            ->where('id', '!=', $id)
            ->where(function (Builder $q) use ($date, $id): void {
                $q->whereDate('movement_date', '>', $date)
                    ->orWhere(function (Builder $q2) use ($date, $id): void {
                        $q2->whereDate('movement_date', '=', $date)
                            ->where('id', '>', $id);
                    });
            })
            ->orderBy('movement_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function dimensionQuery(InventoryMovement $movement): Builder
    {
        $query = InventoryMovement::query()
            ->where('tenant_id', $movement->tenant_id)
            ->where('item_id', $movement->item_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->whereIn('status', [InventoryStatus::Posted->value, InventoryStatus::Reversed->value]);

        if ($movement->organization_unit_id === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where('organization_unit_id', $movement->organization_unit_id);
        }

        if ($movement->warehouse_location_id === null) {
            $query->whereNull('warehouse_location_id');
        } else {
            $query->where('warehouse_location_id', $movement->warehouse_location_id);
        }

        if ($movement->item_variant_id === null) {
            $query->whereNull('item_variant_id');
        } else {
            $query->where('item_variant_id', $movement->item_variant_id);
        }

        if ($movement->batch_id === null) {
            $query->whereNull('batch_id');
        } else {
            $query->where('batch_id', $movement->batch_id);
        }

        return $query;
    }
}
