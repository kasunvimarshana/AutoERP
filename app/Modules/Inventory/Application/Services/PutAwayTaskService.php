<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\PutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\PutAwayTaskRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class PutAwayTaskService implements PutAwayTaskServiceInterface
{
    public function __construct(
        private readonly PutAwayTaskRepositoryInterface $putAwayTaskRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly StockLedgerServiceInterface $stockLedgerService,
    ) {
    }

    public function createTask(array $payload): Result
    {
        try {
            $context = $this->validateTaskContext($payload);
            if ($context->isFailure()) {
                return $context;
            }

            /** @var array{tenant_id:int,item_id:int,uom_id:int,quantity:float} $resolved */
            $resolved = $context->valueOrFail();

            $normalized = $this->normalizeTaskPayload($payload, $resolved);
            if ($normalized->isFailure()) {
                return $normalized;
            }

            return $this->putAwayTaskRepository->transaction(function () use ($payload, $normalized): Result {
                $task = $this->putAwayTaskRepository->create(array_merge(
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
            $existing = $this->putAwayTaskRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'PutAwayTask not found.'));
            }

            $alreadyCompleted = $this->isCompleted($existing);
            if ($alreadyCompleted && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Completed put-away tasks are immutable.',
                ));
            }

            return $this->putAwayTaskRepository->transaction(function () use (
                $id,
                $payload,
                $alreadyCompleted,
            ): Result {
                $updated = $this->putAwayTaskRepository->update($id, $payload);

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
     * @return Result<array{tenant_id:int,item_id:int,uom_id:int,quantity:float}>
     */
    private function validateTaskContext(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $warehouseId = isset($payload['target_warehouse_id']) ? (int) $payload['target_warehouse_id'] : null;
        $uomId = isset($payload['transaction_uom_id'])
            ? (int) $payload['transaction_uom_id']
            : (isset($payload['uom_id']) ? (int) $payload['uom_id'] : null);
        $quantity = (float) ($payload['quantity'] ?? 0);

        if ($tenantId === null || $itemId === null || $warehouseId === null || $uomId === null || $quantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id, target_warehouse_id, transaction_uom_id and quantity are required.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Put-away tasks must reference stockable items in the current tenant.',
            ));
        }

        return Result::success([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{tenant_id:int,item_id:int,uom_id:int,quantity:float} $resolved
     * @return Result<array<string, mixed>>
     */
    private function normalizeTaskPayload(array $payload, array $resolved): Result
    {
        $item = $this->itemRepository->findByIdInTenant($resolved['item_id'], $resolved['tenant_id']);
        if ($item === null) {
            return Result::failure(new Error(InventoryErrorCode::ITEM_NOT_FOUND, 'Item not found for tenant.'));
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $baseQuantity = $this->uomConversionService->convert(
            $resolved['quantity'],
            $resolved['uom_id'],
            $baseUomId,
            $resolved['tenant_id'],
            $resolved['item_id'],
        );

        if ($baseQuantity->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $baseQuantity->errorOrFail()->message,
            ));
        }

        return Result::success([
            'tenant_id' => $resolved['tenant_id'],
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'receipt_inspection_id' => $payload['receipt_inspection_id'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'source_line_id' => $payload['source_line_id'] ?? null,
            'item_id' => $resolved['item_id'],
            'variant_id' => $payload['variant_id'] ?? null,
            'batch_id' => $payload['batch_id'] ?? null,
            'serial_id' => $payload['serial_id'] ?? null,
            'from_warehouse_id' => $payload['from_warehouse_id'] ?? null,
            'from_location_id' => $payload['from_location_id'] ?? null,
            'target_warehouse_id' => $payload['target_warehouse_id'],
            'target_location_id' => $payload['target_location_id'] ?? null,
            'transaction_uom_id' => $resolved['uom_id'],
            'base_uom_id' => $baseUomId,
            'quantity' => $resolved['quantity'],
            'base_quantity' => (float) $baseQuantity->valueOrFail(),
            'notes' => $payload['notes'] ?? null,
            'assigned_user_id' => $payload['assigned_user_id'] ?? null,
        ]);
    }

    private function completeTask(DataRecord $task): Result
    {
        $quantity = (float) $task->get('quantity', 0);
        if ($quantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Completed put-away tasks require quantity greater than zero.',
            ));
        }

        $performedAt = $task->get('completed_at') ?? now();
        $movementResult = $this->stockLedgerService->recordMovement([
            'tenant_id' => (int) $task->get('tenant_id'),
            'organization_unit_id' => $task->get('organization_unit_id'),
            'item_id' => (int) $task->get('item_id'),
            'variant_id' => $task->get('variant_id'),
            'warehouse_id' => (int) $task->get('target_warehouse_id'),
            'location_id' => $task->get('target_location_id'),
            'batch_id' => $task->get('batch_id'),
            'serial_id' => $task->get('serial_id'),
            'uom_id' => (int) $task->get('transaction_uom_id'),
            'quantity' => $quantity,
            'direction' => 'IN',
            'movement_type' => 'PUT_AWAY_TASK',
            'performed_at' => $performedAt,
            'notes' => $task->get('notes'),
            'source_type' => 'put_away_task',
            'source_id' => $task->id(),
        ]);

        if ($movementResult->isFailure()) {
            return $movementResult;
        }

        return Result::success($this->putAwayTaskRepository->update($task->id(), [
            'stock_movement_id' => $movementResult->valueOrFail()->id(),
            'status' => 'COMPLETED',
            'completed_at' => $performedAt,
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
                'receipt_inspection_id',
                'source_type',
                'source_id',
                'source_line_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'from_warehouse_id',
                'from_location_id',
                'target_warehouse_id',
                'target_location_id',
                'transaction_uom_id',
                'uom_id',
                'quantity',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
