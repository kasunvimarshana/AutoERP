<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TransferOrderServiceInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class TransferOrderService implements TransferOrderServiceInterface
{
    public function __construct(private readonly TransferOrderRepositoryInterface $transferOrderRepository)
    {
    }

    public function createOrder(array $payload): Result
    {
        try {
            $validated = $this->validatePayload($payload, true);
            if ($validated->isFailure()) {
                return $validated;
            }

            $payload['row_version'] ??= 1;
            $payload['status'] ??= 'DRAFT';

            return Result::success($this->transferOrderRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateOrder(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->transferOrderRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TransferOrder not found.'));
            }

            if ($this->isFinalized($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Finalized transfer orders are immutable.',
                ));
            }

            $validated = $this->validatePayload(array_merge($existing->toArray(), $payload), false);
            if ($validated->isFailure()) {
                return $validated;
            }

            return Result::success($this->transferOrderRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload, bool $creating): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $fromWarehouseId = isset($payload['from_warehouse_id']) ? (int) $payload['from_warehouse_id'] : null;
        $toWarehouseId = isset($payload['to_warehouse_id']) ? (int) $payload['to_warehouse_id'] : null;
        $transferNumber = trim((string) ($payload['transfer_number'] ?? ''));
        $requestDate = $payload['request_date'] ?? null;

        if (
            $tenantId === null
            || $fromWarehouseId === null
            || $toWarehouseId === null
            || $transferNumber === ''
            || $requestDate === null
        ) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, from_warehouse_id, to_warehouse_id, transfer_number and request_date are required.',
            ));
        }

        if ($fromWarehouseId === $toWarehouseId) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'from_warehouse_id and to_warehouse_id must be different.',
            ));
        }

        if (array_key_exists('status', $payload)) {
            $status = strtoupper((string) $payload['status']);
            if (! in_array($status, ['DRAFT', 'PENDING', 'COMPLETED', 'CANCELLED'], true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'status must be DRAFT, PENDING, COMPLETED or CANCELLED.',
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
                'from_warehouse_id',
                'to_warehouse_id',
                'transfer_number',
                'request_date',
                'expected_date',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isFinalized(DataRecord $order): bool
    {
        return in_array(strtoupper((string) $order->get('status')), ['COMPLETED', 'CANCELLED'], true);
    }
}
