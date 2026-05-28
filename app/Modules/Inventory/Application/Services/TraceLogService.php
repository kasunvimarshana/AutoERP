<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TraceLogServiceInterface;
use Modules\Inventory\Application\Repositories\TraceLogRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class TraceLogService implements TraceLogServiceInterface
{
    public function __construct(private readonly TraceLogRepositoryInterface $traceLogRepository)
    {
    }

    public function createTraceLog(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;

            return Result::success($this->traceLogRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateTraceLog(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->traceLogRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TraceLog not found.'));
            }

            if ($this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Trace logs are immutable aside from metadata and notes. ',
                ));
            }

            return Result::success($this->traceLogRepository->update($id, $payload));
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
        $entityType = trim((string) ($payload['entity_type'] ?? ''));
        $entityId = isset($payload['entity_id']) ? (int) $payload['entity_id'] : null;
        $actionType = trim((string) ($payload['action_type'] ?? ''));

        if ($tenantId === null || $entityType === '' || $entityId === null || $actionType === '') {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, entity_type, entity_id and action_type are required.',
            ));
        }

        if (isset($payload['quantity']) && (float) $payload['quantity'] < 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'quantity cannot be negative.',
            ));
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
                'entity_type',
                'entity_id',
                'identifier_id',
                'action_type',
                'reference_type',
                'reference_id',
                'source_warehouse_id',
                'destination_warehouse_id',
                'source_location_id',
                'destination_location_id',
                'quantity',
                'performed_by',
                'performed_at',
                'device_id',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
