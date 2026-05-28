<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TransferOrderLineServiceInterface;
use Modules\Inventory\Application\Repositories\TransferOrderLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class TransferOrderLineService implements TransferOrderLineServiceInterface
{
    public function __construct(
        private readonly TransferOrderLineRepositoryInterface $transferOrderLineRepository,
        private readonly TransferOrderRepositoryInterface $transferOrderRepository,
    ) {
    }

    public function createLine(array $payload): Result
    {
        try {
            $validated = $this->validatePayload($payload);
            if ($validated->isFailure()) {
                return $validated;
            }

            $payload['row_version'] ??= 1;
            $payload['shipped_qty'] ??= 0;
            $payload['received_qty'] ??= 0;

            return Result::success($this->transferOrderLineRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateLine(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->transferOrderLineRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TransferOrderLine not found.'));
            }

            if ($this->isFinalized($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Finalized transfer order lines are immutable.',
                ));
            }

            $validated = $this->validatePayload(array_merge($existing->toArray(), $payload));
            if ($validated->isFailure()) {
                return $validated;
            }

            return Result::success($this->transferOrderLineRepository->update($id, $payload));
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
        $transferOrderId = isset($payload['transfer_order_id']) ? (int) $payload['transfer_order_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $uomId = isset($payload['uom_id']) ? (int) $payload['uom_id'] : null;
        $requestedQty = (float) ($payload['requested_qty'] ?? 0);
        $shippedQty = (float) ($payload['shipped_qty'] ?? 0);
        $receivedQty = (float) ($payload['received_qty'] ?? 0);

        if (
            $tenantId === null
            || $transferOrderId === null
            || $itemId === null
            || $uomId === null
            || $requestedQty <= 0
        ) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, transfer_order_id, item_id, uom_id and requested_qty (> 0) are required.',
            ));
        }

        $order = $this->transferOrderRepository->findById($transferOrderId);
        if ($order === null || (int) $order->get('tenant_id') !== $tenantId) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'transfer_order_id must reference an existing transfer order in the same tenant.',
            ));
        }

        if ($shippedQty < 0 || $receivedQty < 0 || $shippedQty > $requestedQty || $receivedQty > $requestedQty) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'shipped_qty and received_qty must be between 0 and requested_qty.',
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
                'transfer_order_id',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'from_location_id',
                'to_location_id',
                'uom_id',
                'requested_qty',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isFinalized(DataRecord $line): bool
    {
        return (float) $line->get('received_qty', 0) >= (float) $line->get('requested_qty', 0)
            && (float) $line->get('requested_qty', 0) > 0;
    }
}
