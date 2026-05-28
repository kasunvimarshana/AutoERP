<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\CycleCountLineServiceInterface;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CycleCountLineService implements CycleCountLineServiceInterface
{
    public function __construct(private readonly CycleCountLineRepositoryInterface $repository)
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
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'CycleCountLine not found.'));
            }

            if ($this->isPosted($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted cycle count lines are immutable.',
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
        $headerId = isset($payload['count_header_id']) ? (int) $payload['count_header_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $countedQty = (float) ($payload['counted_qty'] ?? 0);
        $systemQty = (float) ($payload['system_qty'] ?? 0);

        if ($tenantId === null || $headerId === null || $itemId === null || $countedQty < 0 || $systemQty < 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, count_header_id, item_id, counted_qty and system_qty are required.',
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
                'count_header_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'location_id',
                'transaction_uom_id',
                'base_uom_id',
                'uom_id',
                'system_qty',
                'counted_qty',
                'variance_qty',
                'base_system_qty',
                'base_counted_qty',
                'base_variance_qty',
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
