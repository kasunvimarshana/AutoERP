<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryTransfer;
use Modules\Inventory\Models\InventoryTransferLine;
use Modules\Inventory\Validators\InventoryValidationService;

final class StockTransferService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryNumberService $numbers,
        private readonly StockMovementService $movements,
        private readonly InventoryUomService $uoms,
        private readonly StockBalanceService $balances,
    ) {}

    public function create(StockTransferData $data): InventoryTransfer
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Inventory transfer requires at least one line.');
        }

        if ($data->fromWarehouseId === $data->toWarehouseId && $data->fromWarehouseLocationId === $data->toWarehouseLocationId) {
            throw new InvalidArgumentException('Inventory transfer source and destination cannot be the same.');
        }

        $from = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->fromWarehouseId);
        $to = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->toWarehouseId);
        $this->validator->location($from, $data->fromWarehouseLocationId);
        $this->validator->location($to, $data->toWarehouseLocationId);

        return DB::transaction(function () use ($data): InventoryTransfer {
            $transfer = InventoryTransfer::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'transfer_number' => $data->transferNumber ?? $this->numbers->next($data->tenantId, 'TRF', 'inventory_transfers', 'transfer_number'),
                'transfer_date' => $data->transferDate,
                'from_warehouse_id' => $data->fromWarehouseId,
                'from_warehouse_location_id' => $data->fromWarehouseLocationId,
                'to_warehouse_id' => $data->toWarehouseId,
                'to_warehouse_location_id' => $data->toWarehouseLocationId,
                'status' => TransferStatus::Pending,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $this->createLine($transfer, $line);
            }

            return $transfer->refresh()->load('lines');
        });
    }

    public function post(InventoryTransfer $transfer, ?int $postedBy = null): InventoryTransfer
    {
        return $this->dispatch($transfer, $postedBy);
    }

    public function dispatch(InventoryTransfer $transfer, ?int $dispatchedBy = null): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $dispatchedBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($transfer->getKey());
            if (! in_array($transfer->status, [TransferStatus::Pending, TransferStatus::Draft, TransferStatus::Approved], true)) {
                throw new InvalidArgumentException('Only pending inventory transfers can be dispatched.');
            }

            foreach ($transfer->lines as $line) {
                if ($this->math->compare((string) $line->dispatched_quantity, '0.000000') > 0) {
                    throw new InvalidArgumentException('Inventory transfer line has already been dispatched.');
                }

                $outbound = $this->movements->record(new StockMovementData(
                    tenantId: (int) $transfer->tenant_id,
                    movementDate: $transfer->transfer_date->toDateString(),
                    movementType: InventoryMovementType::TransferOut,
                    direction: InventoryDirection::Out,
                    itemId: (int) $line->item_id,
                    warehouseId: (int) $transfer->from_warehouse_id,
                    quantity: (string) $line->quantity,
                    organizationUnitId: $transfer->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $transfer->from_warehouse_location_id,
                    batchId: $line->batch_id,
                    serialNumberId: $line->serial_number_id,
                    unitCost: (string) $line->unit_cost,
                    sourceType: 'inventory_transfer',
                    sourceId: (int) $transfer->getKey(),
                    sourceLineType: 'inventory_transfer_line',
                    sourceLineId: (int) $line->getKey(),
                    fromState: InventoryStockState::Available,
                    toState: InventoryStockState::InTransit,
                ), $dispatchedBy);

                $line->dispatched_quantity = $line->quantity;
                $line->unit_cost = $outbound->unit_cost;
                $line->total_cost = $outbound->total_cost;
                $line->outbound_movement_id = $outbound->getKey();
                $line->save();
                $this->balances->increaseInTransit($this->destinationBalance($transfer, $line), (string) $line->quantity);
            }

            $transfer->status = TransferStatus::Dispatched;
            $transfer->posted_by = $dispatchedBy;
            $transfer->posted_at = now();
            $transfer->dispatched_by = $dispatchedBy;
            $transfer->dispatched_at = now();
            $transfer->save();

            return $transfer->refresh()->load('lines');
        });
    }

    public function receive(InventoryTransfer $transfer, ?int $receivedBy = null): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($transfer->getKey());
            if (! in_array($transfer->status, [TransferStatus::Dispatched, TransferStatus::InTransit], true)) {
                throw new InvalidArgumentException('Only dispatched inventory transfers can be received.');
            }

            foreach ($transfer->lines as $line) {
                $remaining = $this->math->sub(
                    $this->math->sub((string) $line->quantity, (string) $line->received_quantity),
                    (string) $line->cancelled_quantity,
                );
                if ($this->math->isZero($remaining)) {
                    continue;
                }
                $inbound = $this->movements->record(new StockMovementData(
                    tenantId: (int) $transfer->tenant_id,
                    movementDate: $transfer->transfer_date->toDateString(),
                    movementType: InventoryMovementType::TransferIn,
                    direction: InventoryDirection::In,
                    itemId: (int) $line->item_id,
                    warehouseId: (int) $transfer->to_warehouse_id,
                    quantity: $remaining,
                    organizationUnitId: $transfer->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $transfer->to_warehouse_location_id,
                    batchId: $line->batch_id,
                    serialNumberId: $line->serial_number_id,
                    unitCost: (string) $line->unit_cost,
                    sourceType: 'inventory_transfer',
                    sourceId: (int) $transfer->getKey(),
                    sourceLineType: 'inventory_transfer_line',
                    sourceLineId: (int) $line->getKey(),
                    fromState: InventoryStockState::InTransit,
                    toState: InventoryStockState::Available,
                ), $receivedBy);

                $line->received_quantity = $this->math->add((string) $line->received_quantity, $remaining);
                $line->inbound_movement_id = $inbound->getKey();
                $line->save();
                $this->balances->releaseInTransit($this->destinationBalance($transfer, $line), $remaining);
            }

            $transfer->status = TransferStatus::Received;
            $transfer->received_by = $receivedBy;
            $transfer->received_at = now();
            $transfer->save();

            return $transfer->refresh();
        });
    }

    public function reverse(InventoryTransfer $transfer, ?int $reversedBy = null): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $reversedBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()->with('lines')->lockForUpdate()->findOrFail($transfer->getKey());
            if (! in_array($transfer->status, [TransferStatus::Dispatched, TransferStatus::InTransit, TransferStatus::Received, TransferStatus::Posted], true)) {
                throw new InvalidArgumentException('Only dispatched or received inventory transfers can be reversed.');
            }

            if (in_array($transfer->status, [TransferStatus::Dispatched, TransferStatus::InTransit], true)) {
                foreach ($transfer->lines as $line) {
                    $remaining = $this->math->sub(
                        $this->math->sub((string) $line->quantity, (string) $line->received_quantity),
                        (string) $line->cancelled_quantity,
                    );
                    if (! $this->math->isZero($remaining)) {
                        $this->balances->releaseInTransit($this->destinationBalance($transfer, $line), $remaining);
                    }
                }
            }

            $movements = InventoryMovement::query()
                ->where('source_type', 'inventory_transfer')
                ->where('source_id', $transfer->getKey())
                ->where('status', InventoryStatus::Posted->value)
                ->orderByRaw('CASE WHEN movement_type = ? THEN 0 ELSE 1 END', [InventoryMovementType::TransferIn->value])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            foreach ($movements as $movement) {
                $this->movements->reverse($movement, $reversedBy);
            }

            $transfer->status = TransferStatus::Reversed;
            $transfer->reversed_by = $reversedBy;
            $transfer->reversed_at = now();
            $transfer->save();

            return $transfer->refresh();
        });
    }

    public function cancel(InventoryTransfer $transfer, ?int $cancelledBy = null): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $cancelledBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->getKey());
            if (! in_array($transfer->status, [TransferStatus::Pending, TransferStatus::Draft, TransferStatus::Approved], true)) {
                throw new InvalidArgumentException('Only pending inventory transfers can be cancelled.');
            }

            $transfer->status = TransferStatus::Cancelled;
            $transfer->cancelled_by = $cancelledBy;
            $transfer->cancelled_at = now();
            $transfer->save();

            return $transfer->refresh();
        });
    }

    private function destinationBalance(InventoryTransfer $transfer, InventoryTransferLine $line): InventoryStockBalance
    {
        return $this->balances->getOrCreateForUpdate(new StockBalanceData(
            tenantId: (int) $transfer->tenant_id,
            itemId: (int) $line->item_id,
            warehouseId: (int) $transfer->to_warehouse_id,
            organizationUnitId: $transfer->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $transfer->to_warehouse_location_id,
            batchId: $line->batch_id,
        ));
    }

    private function createLine(InventoryTransfer $transfer, StockTransferLineData $data): InventoryTransferLine
    {
        $quantity = $this->math->normalize($data->quantity);
        $unitCost = $this->math->normalize($data->unitCost);
        $this->validator->assertPositiveQuantity($quantity);
        $this->validator->assertNonNegative($unitCost, 'Inventory transfer unit cost cannot be negative.');
        $item = $this->validator->item((int) $transfer->tenant_id, $transfer->organization_unit_id, $data->itemId);
        if ($data->uomId !== null) {
            $basis = $this->uoms->basis((int) $transfer->tenant_id, $transfer->organization_unit_id, $item, $data->uomId, $quantity, $unitCost);
            $quantity = $basis['quantity'];
            $unitCost = $basis['unit_cost'];
            $item = $item->refresh();
        }
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $this->validator->batch($item, $data->batchId);
        $this->validator->serial($item, $data->serialNumberId, $quantity);

        return InventoryTransferLine::query()->create([
            'tenant_id' => $transfer->tenant_id,
            'organization_unit_id' => $transfer->organization_unit_id,
            'inventory_transfer_id' => $transfer->getKey(),
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $this->math->mul($quantity, $unitCost),
        ]);
    }
}
