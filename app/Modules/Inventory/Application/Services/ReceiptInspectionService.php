<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\ReceiptInspectionServiceInterface;
use Modules\Inventory\Application\Repositories\ReceiptInspectionRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class ReceiptInspectionService implements ReceiptInspectionServiceInterface
{
    public function __construct(
        private readonly ReceiptInspectionRepositoryInterface $receiptInspectionRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
    ) {
    }

    public function createInspection(array $payload): Result
    {
        try {
            $resolved = $this->normalizeInspectionPayload($payload);
            if ($resolved->isFailure()) {
                return $resolved;
            }

            return Result::success($this->receiptInspectionRepository->create(array_merge(
                $resolved->valueOrFail(),
                [
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'inspection_status' => $payload['inspection_status'] ?? 'PENDING',
                ],
            )));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateInspection(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->receiptInspectionRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'ReceiptInspection not found.'));
            }

            if ($this->isFinalized($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Finalized receipt inspections are immutable.',
                ));
            }

            $resolved = $this->normalizeInspectionPayload(array_merge($existing->toArray(), $payload));
            if ($resolved->isFailure()) {
                return $resolved;
            }

            return Result::success($this->receiptInspectionRepository->update($id, array_merge(
                $resolved->valueOrFail(),
                [
                    'inspection_status' => $payload['inspection_status'] ?? $existing->get('inspection_status'),
                ],
            )));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return Result<array<string, mixed>>
     */
    private function normalizeInspectionPayload(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $uomId = isset($payload['transaction_uom_id'])
            ? (int) $payload['transaction_uom_id']
            : (isset($payload['uom_id']) ? (int) $payload['uom_id'] : null);
        $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;
        $receivedQuantity = (float) ($payload['received_quantity'] ?? 0);
        $acceptedQuantity = (float) ($payload['accepted_quantity'] ?? 0);
        $rejectedQuantity = (float) ($payload['rejected_quantity'] ?? 0);
        $damagedQuantity = (float) ($payload['damaged_quantity'] ?? 0);

        if ($tenantId === null || $itemId === null || $uomId === null || $receivedQuantity < 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id, transaction_uom_id and received_quantity are required.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Receipt inspections must reference stockable items in the current tenant.',
            ));
        }

        if ($acceptedQuantity < 0 || $rejectedQuantity < 0 || $damagedQuantity < 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'accepted_quantity, rejected_quantity and damaged_quantity cannot be negative.',
            ));
        }

        if (($acceptedQuantity + $rejectedQuantity + $damagedQuantity) > $receivedQuantity) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'accepted, rejected and damaged quantities cannot exceed received quantity.',
            ));
        }

        $baseQuantity = $this->uomConversionService->convert(
            $receivedQuantity,
            $uomId,
            (int) $item->get('base_uom_id'),
            $tenantId,
            $itemId,
        );

        if ($baseQuantity->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $baseQuantity->errorOrFail()->message,
            ));
        }

        return Result::success([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'source_line_id' => $payload['source_line_id'] ?? null,
            'item_id' => $itemId,
            'variant_id' => $payload['variant_id'] ?? null,
            'warehouse_id' => $warehouseId,
            'location_id' => $payload['location_id'] ?? null,
            'transaction_uom_id' => $uomId,
            'base_uom_id' => (int) $item->get('base_uom_id'),
            'received_quantity' => $receivedQuantity,
            'base_received_quantity' => (float) $baseQuantity->valueOrFail(),
            'accepted_quantity' => $acceptedQuantity,
            'rejected_quantity' => $rejectedQuantity,
            'damaged_quantity' => $damagedQuantity,
            'inspected_by' => $payload['inspected_by'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'inspected_at' => $payload['inspected_at'] ?? null,
        ]);
    }

    private function isFinalized(DataRecord $inspection): bool
    {
        return in_array(
            strtoupper((string) $inspection->get('inspection_status')),
            ['ACCEPTED', 'REJECTED', 'CANCELLED'],
            true,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'source_type',
                'source_id',
                'source_line_id',
                'item_id',
                'variant_id',
                'warehouse_id',
                'location_id',
                'transaction_uom_id',
                'uom_id',
                'received_quantity',
                'accepted_quantity',
                'rejected_quantity',
                'damaged_quantity',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
