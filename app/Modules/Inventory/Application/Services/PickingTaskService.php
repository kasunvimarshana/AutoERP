<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\PickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockReservationServiceInterface;
use Modules\Inventory\Application\Repositories\PickingTaskRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class PickingTaskService implements PickingTaskServiceInterface
{
    public function __construct(
        private readonly PickingTaskRepositoryInterface $pickingTaskRepository,
        private readonly StockReservationRepositoryInterface $stockReservationRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly StockLedgerServiceInterface $stockLedgerService,
        private readonly StockReservationServiceInterface $stockReservationService,
    ) {
    }

    public function createTask(array $payload): Result
    {
        try {
            $context = $this->validateTaskContext($payload);
            if ($context->isFailure()) {
                return $context;
            }

            /** @var array{tenant_id:int,item_id:int,uom_id:int,reserved_quantity:float,picked_quantity:float} $resolved */
            $resolved = $context->valueOrFail();

            $normalized = $this->normalizeTaskPayload($payload, $resolved);
            if ($normalized->isFailure()) {
                return $normalized;
            }

            return $this->pickingTaskRepository->transaction(function () use ($payload, $normalized): Result {
                $task = $this->pickingTaskRepository->create(array_merge(
                    $normalized->valueOrFail(),
                    [
                        'row_version' => (int) ($payload['row_version'] ?? 1),
                        'stock_movement_id' => null,
                        'status' => $payload['status'] ?? 'PENDING',
                    ],
                ));

                if ($this->shouldComplete($task->get('status'))) {
                    return $this->completeTask($task);
                }

                return Result::success($task);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateTask(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->pickingTaskRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'PickingTask not found.'));
            }

            $alreadyCompleted = $this->isCompleted($existing);
            if ($alreadyCompleted && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Completed picking tasks are immutable.',
                ));
            }

            return $this->pickingTaskRepository->transaction(function () use (
                $id,
                $payload,
                $alreadyCompleted,
            ): Result {
                $updated = $this->pickingTaskRepository->update($id, $payload);

                if ($this->shouldComplete($updated->get('status')) && ! $alreadyCompleted) {
                    return $this->completeTask($updated);
                }

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return Result<array{tenant_id:int,item_id:int,uom_id:int,reserved_quantity:float,picked_quantity:float}>
     */
    private function validateTaskContext(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $warehouseId = isset($payload['source_warehouse_id']) ? (int) $payload['source_warehouse_id'] : null;
        $uomId = isset($payload['transaction_uom_id'])
            ? (int) $payload['transaction_uom_id']
            : (isset($payload['uom_id']) ? (int) $payload['uom_id'] : null);
        $reservedQuantity = (float) ($payload['reserved_quantity'] ?? 0);
        $pickedQuantity = (float) ($payload['picked_quantity'] ?? 0);

        if ($tenantId === null || $itemId === null || $warehouseId === null || $uomId === null) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id, source_warehouse_id and transaction_uom_id are required.',
            ));
        }

        if ($reservedQuantity < 0 || $pickedQuantity < 0 || $pickedQuantity > $reservedQuantity) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'picked_quantity must be between 0 and reserved_quantity.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Picking tasks must reference stockable items in the current tenant.',
            ));
        }

        $reservationId = isset($payload['stock_reservation_id']) ? (int) $payload['stock_reservation_id'] : null;
        if ($reservationId !== null) {
            $reservation = $this->stockReservationRepository->findById($reservationId);
            if (
                $reservation === null
                || (int) $reservation->get('tenant_id') !== $tenantId
                || (int) $reservation->get('item_id') !== $itemId
                || (int) $reservation->get('warehouse_id') !== $warehouseId
            ) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'stock_reservation_id must match the same tenant, item and source warehouse.',
                ));
            }
        }

        return Result::success([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'reserved_quantity' => $reservedQuantity,
            'picked_quantity' => $pickedQuantity,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{tenant_id:int,item_id:int,uom_id:int,reserved_quantity:float,picked_quantity:float} $resolved
     * @return Result<array<string, mixed>>
     */
    private function normalizeTaskPayload(array $payload, array $resolved): Result
    {
        $item = $this->itemRepository->findByIdInTenant($resolved['item_id'], $resolved['tenant_id']);
        if ($item === null) {
            return Result::failure(new Error(InventoryErrorCode::ITEM_NOT_FOUND, 'Item not found for tenant.'));
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $baseReserved = $this->uomConversionService->convert(
            $resolved['reserved_quantity'],
            $resolved['uom_id'],
            $baseUomId,
            $resolved['tenant_id'],
            $resolved['item_id'],
        );
        $basePicked = $this->uomConversionService->convert(
            $resolved['picked_quantity'],
            $resolved['uom_id'],
            $baseUomId,
            $resolved['tenant_id'],
            $resolved['item_id'],
        );

        if ($baseReserved->isFailure() || $basePicked->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $baseReserved->isFailure()
                    ? $baseReserved->errorOrFail()->message
                    : $basePicked->errorOrFail()->message,
            ));
        }

        return Result::success([
            'tenant_id' => $resolved['tenant_id'],
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'source_line_id' => $payload['source_line_id'] ?? null,
            'stock_reservation_id' => $payload['stock_reservation_id'] ?? null,
            'item_id' => $resolved['item_id'],
            'variant_id' => $payload['variant_id'] ?? null,
            'batch_id' => $payload['batch_id'] ?? null,
            'serial_id' => $payload['serial_id'] ?? null,
            'source_warehouse_id' => $payload['source_warehouse_id'],
            'source_location_id' => $payload['source_location_id'] ?? null,
            'transaction_uom_id' => $resolved['uom_id'],
            'base_uom_id' => $baseUomId,
            'reserved_quantity' => $resolved['reserved_quantity'],
            'picked_quantity' => $resolved['picked_quantity'],
            'base_reserved_quantity' => (float) $baseReserved->valueOrFail(),
            'base_picked_quantity' => (float) $basePicked->valueOrFail(),
            'assigned_user_id' => $payload['assigned_user_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    private function completeTask(DataRecord $task): Result
    {
        $pickedQuantity = (float) $task->get('picked_quantity', 0);
        if ($pickedQuantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Completed picking tasks require picked_quantity greater than zero.',
            ));
        }

        if ($task->get('stock_reservation_id') !== null) {
            $consume = $this->stockReservationService->consume((int) $task->get('stock_reservation_id'), [
                'quantity' => $pickedQuantity,
            ]);

            if ($consume->isFailure()) {
                return $consume;
            }
        }

        $movement = $this->stockLedgerService->recordMovement([
            'tenant_id' => (int) $task->get('tenant_id'),
            'organization_unit_id' => $task->get('organization_unit_id'),
            'item_id' => (int) $task->get('item_id'),
            'variant_id' => $task->get('variant_id'),
            'warehouse_id' => (int) $task->get('source_warehouse_id'),
            'location_id' => $task->get('source_location_id'),
            'batch_id' => $task->get('batch_id'),
            'serial_id' => $task->get('serial_id'),
            'uom_id' => (int) $task->get('transaction_uom_id'),
            'quantity' => $pickedQuantity,
            'direction' => 'OUT',
            'movement_type' => 'PICKING_TASK',
            'performed_at' => $task->get('completed_at') ?? now(),
            'notes' => $task->get('notes'),
            'source_type' => 'picking_task',
            'source_id' => $task->id(),
        ]);

        if ($movement->isFailure()) {
            return $movement;
        }

        return Result::success($this->pickingTaskRepository->update($task->id(), [
            'stock_movement_id' => $movement->valueOrFail()->id(),
            'status' => 'COMPLETED',
            'completed_at' => $task->get('completed_at') ?? now(),
        ]));
    }

    private function shouldComplete(mixed $status): bool
    {
        return strtoupper((string) $status) === 'COMPLETED';
    }

    private function isCompleted(DataRecord $task): bool
    {
        return $this->shouldComplete($task->get('status')) || $task->get('stock_movement_id') !== null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'stock_reservation_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'source_warehouse_id',
                'source_location_id',
                'transaction_uom_id',
                'uom_id',
                'reserved_quantity',
                'picked_quantity',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
