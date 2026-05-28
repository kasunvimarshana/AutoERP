<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\BatchServiceInterface;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class BatchService implements BatchServiceInterface
{
    public function __construct(private readonly BatchRepositoryInterface $batchRepository)
    {
    }

    public function createBatch(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;
            $payload['status'] ??= 'active';

            return Result::success($this->batchRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateBatch(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->batchRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'Batch not found.'));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload), false);
            if ($validation->isFailure()) {
                return $validation;
            }

            if ($this->isLocked($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Locked batches are immutable.',
                ));
            }

            return Result::success($this->batchRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload, bool $creating = true): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $batchNumber = trim((string) ($payload['batch_number'] ?? ''));

        if ($tenantId === null || $itemId === null || $batchNumber === '') {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id and batch_number are required.',
            ));
        }

        if (array_key_exists('status', $payload) && $payload['status'] !== null) {
            $status = strtolower((string) $payload['status']);
            if (! in_array($status, ['active', 'inactive', 'archived'], true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'status must be active, inactive or archived.',
                ));
            }
        }

        return Result::success(true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'tenant_id',
                'organization_unit_id',
                'item_id',
                'variant_id',
                'batch_number',
                'lot_number',
                'manufacture_date',
                'expiry_date',
                'received_date',
                'supplier_id',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isLocked(DataRecord $batch): bool
    {
        return in_array(strtolower((string) $batch->get('status')), ['inactive', 'archived'], true);
    }
}
