<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockTransferLineServiceInterface;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class StockTransferLineService implements StockTransferLineServiceInterface
{
    public function __construct(private readonly StockTransferLineRepositoryInterface $repository)
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
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockTransferLine not found.'));
            }

            if ($this->isPosted($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted stock transfer lines are immutable.',
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
        $transferId = isset($payload['stock_transfer_id']) ? (int) $payload['stock_transfer_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $quantity = (float) ($payload['quantity'] ?? 0);

        if ($tenantId === null || $transferId === null || $itemId === null || $quantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, stock_transfer_id, item_id and quantity (> 0) are required.',
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
                'stock_transfer_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'from_location_id',
                'to_location_id',
                'uom_id',
                'quantity',
                'base_quantity',
                'unit_cost',
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
        return $record->get('outgoing_movement_id') !== null || $record->get('incoming_movement_id') !== null;
    }
}
