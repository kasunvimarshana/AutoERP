<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class StockAdjustmentLineService implements StockAdjustmentLineServiceInterface
{
    public function __construct(private readonly StockAdjustmentLineRepositoryInterface $repository)
    {
    }

    public function createLine(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateLine(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->repository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockAdjustmentLine not found.'));
            }

            if ($this->isPosted($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted stock adjustment lines are immutable.',
                ));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload));
            if ($validation->isFailure()) {
                return $validation;
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $adjustmentId = isset($payload['stock_adjustment_id']) ? (int) $payload['stock_adjustment_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;
        $adjustmentQty = (float) ($payload['adjustment_quantity'] ?? 0);

        if (
            $tenantId === null
            || $adjustmentId === null
            || $itemId === null
            || $warehouseId === null
            || $adjustmentQty == 0.0
        ) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, stock_adjustment_id, item_id, warehouse_id and adjustment_quantity are required.',
            ));
        }

        return Result::success(true);
    }

    /** @param array<string, mixed> $payload */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'tenant_id',
                'organization_unit_id',
                'stock_adjustment_id',
                'warehouse_id',
                'location_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'transaction_uom_id',
                'base_uom_id',
                'adjustment_quantity',
                'base_adjustment_quantity',
                'direction',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isPosted(DataRecord $record): bool
    {
        return $record->get('adjustment_movement_id') !== null;
    }
}
