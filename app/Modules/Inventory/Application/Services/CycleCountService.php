<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\CycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class CycleCountService implements CycleCountServiceInterface
{
    public function __construct(
        private readonly CycleCountHeaderRepositoryInterface $headerRepository,
        private readonly CycleCountLineRepositoryInterface $lineRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly StockLedgerServiceInterface $stockLedgerService,
    ) {
    }

    public function createCount(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
            $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;

            if ($tenantId === null || $warehouseId === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id and warehouse_id are required for cycle counts.',
                ));
            }

            /** @var list<array<string, mixed>> $lines */
            $lines = array_values(array_filter((array) ($payload['lines'] ?? []), 'is_array'));

            foreach ($lines as $line) {
                $lineValidation = $this->validateCountLine($tenantId, $line);
                if ($lineValidation->isFailure()) {
                    return $lineValidation;
                }
            }

            return $this->headerRepository->transaction(function () use ($payload, $tenantId, $lines): Result {
                $headerPayload = $payload;
                unset($headerPayload['lines']);

                $header = $this->headerRepository->create(array_merge($headerPayload, [
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'status' => $payload['status'] ?? 'draft',
                ]));

                foreach ($lines as $line) {
                    $normalizedLine = $this->normalizeCountLine($tenantId, $line);
                    if ($normalizedLine->isFailure()) {
                        return $normalizedLine;
                    }

                    $this->lineRepository->create(array_merge(
                        $normalizedLine->valueOrFail(),
                        [
                            'row_version' => (int) ($line['row_version'] ?? 1),
                            'tenant_id' => $tenantId,
                            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                            'count_header_id' => $header->id(),
                            'location_id' => $line['location_id'] ?? $payload['location_id'] ?? null,
                            'adjustment_movement_id' => null,
                        ],
                    ));
                }

                if ($this->shouldPost($header->get('status'))) {
                    return $this->postCountRecord($header);
                }

                return Result::success($header);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateCount(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->headerRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'CycleCountHeader not found.'));
            }

            $alreadyPosted = $this->isPosted($existing);

            if ($alreadyPosted && $this->containsHeaderMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted cycle counts are immutable.',
                ));
            }

            return $this->headerRepository->transaction(function () use ($id, $payload, $alreadyPosted): Result {
                $updated = $this->headerRepository->update($id, $payload);

                if ($this->shouldPost($updated->get('status')) && ! $alreadyPosted) {
                    return $this->postCountRecord($updated);
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
    private function validateCountLine(int $tenantId, array $line): Result
    {
        $itemId = isset($line['item_id']) ? (int) $line['item_id'] : null;
        $uomId = isset($line['uom_id']) ? (int) $line['uom_id'] : null;
        $countedQuantity = (float) ($line['counted_qty'] ?? $line['counted_quantity'] ?? 0);

        if ($itemId === null || $uomId === null || $countedQuantity < 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Each cycle count line requires item_id, uom_id and counted_qty >= 0.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Cycle count lines must reference stockable items in the current tenant.',
            ));
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $countedConversion = $this->uomConversionService->convert(
            $countedQuantity,
            $uomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );
        $systemQuantity = (float) ($line['system_qty'] ?? $line['system_quantity'] ?? 0);
        $systemConversion = $this->uomConversionService->convert(
            $systemQuantity,
            $uomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );

        if ($countedConversion->isFailure() || $systemConversion->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $countedConversion->isFailure()
                    ? $countedConversion->errorOrFail()->message
                    : $systemConversion->errorOrFail()->message,
            ));
        }

        return Result::success(true);
    }

    /**
     * @param array<string, mixed> $line
     * @return Result<array<string, mixed>>
     */
    private function normalizeCountLine(int $tenantId, array $line): Result
    {
        $itemId = (int) $line['item_id'];
        $uomId = (int) $line['uom_id'];
        $systemQuantity = (float) ($line['system_qty'] ?? $line['system_quantity'] ?? 0);
        $countedQuantity = (float) ($line['counted_qty'] ?? $line['counted_quantity'] ?? 0);

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null) {
            return Result::failure(new Error(InventoryErrorCode::ITEM_NOT_FOUND, 'Item not found for tenant.'));
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $systemConversion = $this->uomConversionService->convert(
            $systemQuantity,
            $uomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );
        $countedConversion = $this->uomConversionService->convert(
            $countedQuantity,
            $uomId,
            $baseUomId,
            $tenantId,
            $itemId,
        );

        if ($systemConversion->isFailure() || $countedConversion->isFailure()) {
            return Result::failure(new Error(
                InventoryErrorCode::UOM_CONVERSION_FAILED,
                $systemConversion->isFailure()
                    ? $systemConversion->errorOrFail()->message
                    : $countedConversion->errorOrFail()->message,
            ));
        }

        $baseSystem = (float) $systemConversion->valueOrFail();
        $baseCounted = (float) $countedConversion->valueOrFail();

        return Result::success([
            'item_id' => $itemId,
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'transaction_uom_id' => $uomId,
            'base_uom_id' => $baseUomId,
            'system_qty' => $systemQuantity,
            'counted_qty' => $countedQuantity,
            'variance_qty' => $countedQuantity - $systemQuantity,
            'base_system_qty' => $baseSystem,
            'base_counted_qty' => $baseCounted,
            'base_variance_qty' => $baseCounted - $baseSystem,
            'unit_cost' => $line['unit_cost'] ?? null,
            'variance_value' => $line['variance_value'] ?? null,
            'variance_reason' => $line['variance_reason'] ?? null,
            'counted_by_user_id' => $line['counted_by_user_id'] ?? null,
            'notes' => $line['notes'] ?? null,
        ]);
    }

    private function postCountRecord(DataRecord $header): Result
    {
        $lines = $this->lineRepository->list([
            'count_header_id' => $header->id(),
        ]);

        foreach ($lines as $line) {
            if ($line->get('adjustment_movement_id') !== null) {
                continue;
            }

            $varianceQuantity = (float) $line->get('variance_qty', 0);
            if ($varianceQuantity === 0.0) {
                continue;
            }

            $movementResult = $this->stockLedgerService->recordMovement([
                'tenant_id' => (int) $header->get('tenant_id'),
                'organization_unit_id' => $header->get('organization_unit_id'),
                'item_id' => (int) $line->get('item_id'),
                'variant_id' => $line->get('variant_id'),
                'warehouse_id' => (int) $header->get('warehouse_id'),
                'location_id' => $line->get('location_id') ?? $header->get('location_id'),
                'batch_id' => $line->get('batch_id'),
                'serial_id' => $line->get('serial_id'),
                'uom_id' => (int) $line->get('transaction_uom_id'),
                'quantity' => abs($varianceQuantity),
                'direction' => $varianceQuantity > 0 ? 'IN' : 'OUT',
                'movement_type' => 'CYCLE_COUNT_ADJUSTMENT',
                'performed_at' => $header->get('approved_at') ?? $header->get('counted_at') ?? now(),
                'approved_by' => $header->get('approved_by_user_id'),
                'unit_cost' => $line->get('unit_cost'),
                'notes' => $line->get('notes') ?? $line->get('variance_reason'),
                'source_type' => 'cycle_count',
                'source_id' => $header->id(),
                'source_line_id' => $line->id(),
            ]);

            if ($movementResult->isFailure()) {
                return $movementResult;
            }

            $movement = $movementResult->valueOrFail();
            $this->lineRepository->update($line->id(), [
                'adjustment_movement_id' => $movement->id(),
            ]);
        }

        return Result::success($this->headerRepository->update($header->id(), [
            'status' => 'completed',
            'approved_at' => $header->get('approved_at') ?? now(),
        ]));
    }

    private function shouldPost(mixed $status): bool
    {
        return in_array(strtoupper((string) $status), ['COMPLETED', 'POSTED'], true);
    }

    private function isPosted(DataRecord $header): bool
    {
        if ($this->shouldPost($header->get('status'))) {
            return true;
        }

        $lines = $this->lineRepository->list([
            'count_header_id' => $header->id(),
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
        foreach (['warehouse_id', 'location_id', 'lines'] as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
