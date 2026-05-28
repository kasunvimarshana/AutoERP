<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockAdjustmentServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class StockAdjustmentService implements StockAdjustmentServiceInterface
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $adjustmentRepository,
        private readonly StockAdjustmentLineRepositoryInterface $adjustmentLineRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly StockLedgerServiceInterface $stockLedgerService,
    ) {
    }

    public function createAdjustment(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
            $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;

            if ($tenantId === null || $warehouseId === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id and warehouse_id are required for stock adjustments.',
                ));
            }

            /** @var list<array<string, mixed>> $lines */
            $lines = array_values(array_filter((array) ($payload['lines'] ?? []), 'is_array'));

            foreach ($lines as $line) {
                $lineValidation = $this->validateAdjustmentLine($tenantId, $line);
                if ($lineValidation->isFailure()) {
                    return $lineValidation;
                }
            }

            return $this->adjustmentRepository->transaction(function () use ($payload, $tenantId, $lines): Result {
                $headerPayload = $payload;
                unset($headerPayload['lines']);

                $adjustment = $this->adjustmentRepository->create(array_merge($headerPayload, [
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'status' => $payload['status'] ?? 'DRAFT',
                ]));

                foreach ($lines as $line) {
                    $normalizedLine = $this->normalizeAdjustmentLine($tenantId, $line);
                    if ($normalizedLine->isFailure()) {
                        return $normalizedLine;
                    }

                    $this->adjustmentLineRepository->create(array_merge(
                        $normalizedLine->valueOrFail(),
                        [
                            'row_version' => (int) ($line['row_version'] ?? 1),
                            'tenant_id' => $tenantId,
                            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                            'stock_adjustment_id' => $adjustment->id(),
                            'warehouse_id' => $payload['warehouse_id'],
                            'location_id' => $line['location_id'] ?? $payload['location_id'] ?? null,
                            'adjustment_movement_id' => null,
                        ],
                    ));
                }

                if ($this->shouldPost($adjustment->get('status'))) {
                    return $this->postAdjustmentRecord($adjustment);
                }

                return Result::success($adjustment);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateAdjustment(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->adjustmentRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockAdjustment not found.'));
            }

            $alreadyPosted = $this->isPosted($existing);

            if ($alreadyPosted && $this->containsHeaderMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted stock adjustments are immutable.',
                ));
            }

            return $this->adjustmentRepository->transaction(function () use ($id, $payload, $alreadyPosted): Result {
                $updated = $this->adjustmentRepository->update($id, $payload);

                if ($this->shouldPost($updated->get('status')) && ! $alreadyPosted) {
                    return $this->postAdjustmentRecord($updated);
                }

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $line
     */
    private function validateAdjustmentLine(int $tenantId, array $line): Result
    {
        $itemId = isset($line['item_id']) ? (int) $line['item_id'] : null;
        $uomId = isset($line['uom_id']) ? (int) $line['uom_id'] : null;
        $quantity = abs((float) ($line['adjustment_quantity'] ?? $line['quantity'] ?? 0));

        if ($itemId === null || $uomId === null || $quantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Each adjustment line requires item_id, uom_id and adjustment_quantity > 0.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Adjustment lines must reference stockable items in the current tenant.',
            ));
        }

        $conversion = $this->uomConversionService->convert(
            $quantity,
            $uomId,
            (int) $item->get('base_uom_id'),
            $tenantId,
            $itemId,
        );

        if ($conversion->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $conversion->errorOrFail()->message,
            ));
        }

        return Result::success(true);
    }

    /**
     * @param array<string, mixed> $line
     * @return Result<array<string, mixed>>
     */
    private function normalizeAdjustmentLine(int $tenantId, array $line): Result
    {
        $itemId = (int) $line['item_id'];
        $uomId = (int) $line['uom_id'];
        $direction = strtoupper((string) ($line['direction'] ?? 'INCREASE'));
        $quantity = abs((float) ($line['adjustment_quantity'] ?? $line['quantity'] ?? 0));
        $currentQuantity = (float) ($line['current_quantity'] ?? 0);

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null) {
            return Result::failure(new Error(InventoryErrorCode::ITEM_NOT_FOUND, 'Item not found for tenant.'));
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $baseQuantityResult = $this->uomConversionService->convert($quantity, $uomId, $baseUomId, $tenantId, $itemId);
        $baseCurrentResult = $this->uomConversionService->convert(
            $currentQuantity,
            $uomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );

        if ($baseQuantityResult->isFailure() || $baseCurrentResult->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $baseQuantityResult->isFailure()
                    ? $baseQuantityResult->errorOrFail()->message
                    : $baseCurrentResult->errorOrFail()->message,
            ));
        }

        $signedQuantity = $direction === 'DECREASE' ? ($quantity * -1) : $quantity;
        $signedBaseQuantity = $direction === 'DECREASE'
            ? ((float) $baseQuantityResult->valueOrFail() * -1)
            : (float) $baseQuantityResult->valueOrFail();

        return Result::success([
            'item_id' => $itemId,
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'transaction_uom_id' => $uomId,
            'base_uom_id' => $baseUomId,
            'direction' => $direction,
            'current_quantity' => $currentQuantity,
            'base_current_quantity' => (float) $baseCurrentResult->valueOrFail(),
            'adjustment_quantity' => $signedQuantity,
            'base_adjustment_quantity' => $signedBaseQuantity,
            'resulting_quantity' => $currentQuantity + $signedQuantity,
            'base_resulting_quantity' => (float) $baseCurrentResult->valueOrFail() + $signedBaseQuantity,
            'unit_cost' => $line['unit_cost'] ?? null,
            'reason_code' => $line['reason_code'] ?? null,
            'notes' => $line['notes'] ?? null,
        ]);
    }

    private function postAdjustmentRecord(DataRecord $adjustment): Result
    {
        $lines = $this->adjustmentLineRepository->list([
            'stock_adjustment_id' => $adjustment->id(),
        ]);

        foreach ($lines as $line) {
            if ($line->get('adjustment_movement_id') !== null) {
                continue;
            }

            $quantity = abs((float) $line->get('adjustment_quantity', 0));
            if ($quantity <= 0) {
                continue;
            }

            $direction = (float) $line->get('base_adjustment_quantity', 0) >= 0 ? 'IN' : 'OUT';
            $movementResult = $this->stockLedgerService->recordMovement([
                'tenant_id' => (int) $adjustment->get('tenant_id'),
                'organization_unit_id' => $adjustment->get('organization_unit_id'),
                'item_id' => (int) $line->get('item_id'),
                'variant_id' => $line->get('variant_id'),
                'warehouse_id' => (int) $line->get('warehouse_id'),
                'location_id' => $line->get('location_id'),
                'batch_id' => $line->get('batch_id'),
                'serial_id' => $line->get('serial_id'),
                'uom_id' => (int) $line->get('transaction_uom_id'),
                'quantity' => $quantity,
                'direction' => $direction,
                'movement_type' => 'STOCK_ADJUSTMENT',
                'performed_at' => $adjustment->get('approved_at') ?? $adjustment->get('counted_at') ?? now(),
                'approved_by' => $adjustment->get('approved_by'),
                'unit_cost' => $line->get('unit_cost'),
                'notes' => $line->get('notes') ?? $adjustment->get('reason'),
                'source_type' => 'stock_adjustment',
                'source_id' => $adjustment->id(),
                'source_line_id' => $line->id(),
            ]);

            if ($movementResult->isFailure()) {
                return $movementResult;
            }

            $movement = $movementResult->valueOrFail();
            $this->adjustmentLineRepository->update($line->id(), [
                'adjustment_movement_id' => $movement->id(),
            ]);
        }

        return Result::success($this->adjustmentRepository->update($adjustment->id(), [
            'status' => 'COMPLETED',
            'approved_at' => $adjustment->get('approved_at') ?? now(),
        ]));
    }

    private function shouldPost(mixed $status): bool
    {
        return in_array(strtoupper((string) $status), ['COMPLETED', 'POSTED'], true);
    }

    private function isPosted(DataRecord $adjustment): bool
    {
        if ($this->shouldPost($adjustment->get('status'))) {
            return true;
        }

        $lines = $this->adjustmentLineRepository->list([
            'stock_adjustment_id' => $adjustment->id(),
        ]);

        foreach ($lines as $line) {
            if ($line->get('adjustment_movement_id') !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function containsHeaderMutation(array $payload): bool
    {
        foreach (['warehouse_id', 'location_id', 'type', 'reason', 'lines'] as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
