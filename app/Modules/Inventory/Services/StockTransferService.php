<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryMovement;
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
                'status' => TransferStatus::Draft,
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
        return DB::transaction(function () use ($transfer, $postedBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($transfer->getKey());
            if (! in_array($transfer->status, [TransferStatus::Draft, TransferStatus::Approved], true)) {
                throw new InvalidArgumentException('Only draft or approved inventory transfers can be posted.');
            }

            foreach ($transfer->lines as $line) {
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
                ), $postedBy);

                $this->movements->record(new StockMovementData(
                    tenantId: (int) $transfer->tenant_id,
                    movementDate: $transfer->transfer_date->toDateString(),
                    movementType: InventoryMovementType::TransferIn,
                    direction: InventoryDirection::In,
                    itemId: (int) $line->item_id,
                    warehouseId: (int) $transfer->to_warehouse_id,
                    quantity: (string) $line->quantity,
                    organizationUnitId: $transfer->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $transfer->to_warehouse_location_id,
                    batchId: $line->batch_id,
                    serialNumberId: $line->serial_number_id,
                    unitCost: (string) $outbound->unit_cost,
                    sourceType: 'inventory_transfer',
                    sourceId: (int) $transfer->getKey(),
                    sourceLineType: 'inventory_transfer_line',
                    sourceLineId: (int) $line->getKey(),
                ), $postedBy);
            }

            $transfer->status = TransferStatus::Posted;
            $transfer->posted_by = $postedBy;
            $transfer->posted_at = now();
            $transfer->save();

            return $transfer->refresh();
        });
    }

    public function reverse(InventoryTransfer $transfer, ?int $reversedBy = null): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $reversedBy): InventoryTransfer {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->getKey());
            if ($transfer->status !== TransferStatus::Posted) {
                throw new InvalidArgumentException('Only posted inventory transfers can be reversed.');
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
            $transfer->save();

            return $transfer->refresh();
        });
    }

    private function createLine(InventoryTransfer $transfer, StockTransferLineData $data): InventoryTransferLine
    {
        $this->validator->assertPositiveQuantity($data->quantity);
        $this->validator->assertNonNegative($data->unitCost, 'Inventory transfer unit cost cannot be negative.');
        $item = $this->validator->item((int) $transfer->tenant_id, $transfer->organization_unit_id, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $this->validator->batch($item, $data->batchId);
        $this->validator->serial($item, $data->serialNumberId, $data->quantity);

        return InventoryTransferLine::query()->create([
            'tenant_id' => $transfer->tenant_id,
            'organization_unit_id' => $transfer->organization_unit_id,
            'inventory_transfer_id' => $transfer->getKey(),
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'batch_id' => $data->batchId,
            'serial_number_id' => $data->serialNumberId,
            'quantity' => $this->math->normalize($data->quantity),
            'unit_cost' => $this->math->normalize($data->unitCost),
            'total_cost' => $this->math->mul($data->quantity, $data->unitCost),
        ]);
    }
}
