<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockTransferServiceInterface;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class StockTransferService implements StockTransferServiceInterface
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository,
        private readonly StockTransferLineRepositoryInterface $transferLineRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly StockLedgerServiceInterface $stockLedgerService,
    ) {
    }

    public function createTransfer(array $payload): Result
    {
        try {
            $context = $this->validateTransferContext($payload);
            if ($context->isFailure()) {
                return $context;
            }

            /** @var array{tenant_id:int, from_warehouse_id:int, to_warehouse_id:int, lines:list<array<string,mixed>>} $resolved */
            $resolved = $context->valueOrFail();

            foreach ($resolved['lines'] as $line) {
                $lineValidation = $this->validateTransferLine($resolved['tenant_id'], $line);
                if ($lineValidation->isFailure()) {
                    return $lineValidation;
                }
            }

            return $this->transferRepository->transaction(function () use ($payload, $resolved): Result {
                $transferPayload = $payload;
                unset($transferPayload['lines']);

                $transfer = $this->transferRepository->create(array_merge($transferPayload, [
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'status' => $payload['status'] ?? 'DRAFT',
                ]));

                foreach ($resolved['lines'] as $line) {
                    $normalizedLine = $this->normalizeTransferLine($resolved['tenant_id'], $payload, $line);
                    if ($normalizedLine->isFailure()) {
                        return $normalizedLine;
                    }

                    $this->transferLineRepository->create(array_merge(
                        $normalizedLine->valueOrFail(),
                        [
                            'row_version' => (int) ($line['row_version'] ?? 1),
                            'tenant_id' => $resolved['tenant_id'],
                            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                            'stock_transfer_id' => $transfer->id(),
                            'outgoing_movement_id' => null,
                            'incoming_movement_id' => null,
                        ],
                    ));
                }

                if ($this->shouldPost($transfer->get('status'))) {
                    return $this->postTransferRecord($transfer);
                }

                return Result::success($transfer);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateTransfer(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->transferRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockTransfer not found.'));
            }

            $alreadyPosted = $this->isPosted($existing);
            if ($alreadyPosted && $this->containsHeaderMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted stock transfers are immutable.',
                ));
            }

            return $this->transferRepository->transaction(function () use ($id, $payload, $alreadyPosted): Result {
                $updated = $this->transferRepository->update($id, $payload);

                if ($this->shouldPost($updated->get('status')) && ! $alreadyPosted) {
                    return $this->postTransferRecord($updated);
                }

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return Result<array{tenant_id:int, from_warehouse_id:int, to_warehouse_id:int, lines:list<array<string,mixed>>}>
     */
    private function validateTransferContext(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $fromWarehouseId = isset($payload['from_warehouse_id']) ? (int) $payload['from_warehouse_id'] : null;
        $toWarehouseId = isset($payload['to_warehouse_id']) ? (int) $payload['to_warehouse_id'] : null;

        if ($tenantId === null || $fromWarehouseId === null || $toWarehouseId === null) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, from_warehouse_id and to_warehouse_id are required.',
            ));
        }

        if ($fromWarehouseId === $toWarehouseId) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Source and destination warehouses must be different.',
            ));
        }

        /** @var list<array<string, mixed>> $lines */
        $lines = array_values(array_filter((array) ($payload['lines'] ?? []), 'is_array'));

        return Result::success([
            'tenant_id' => $tenantId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'lines' => $lines,
        ]);
    }

    /**
     * @param array<string, mixed> $line
     */
    private function validateTransferLine(int $tenantId, array $line): Result
    {
        $itemId = isset($line['item_id']) ? (int) $line['item_id'] : null;
        $uomId = isset($line['uom_id']) ? (int) $line['uom_id'] : null;
        $quantity = (float) ($line['quantity'] ?? 0);

        if ($itemId === null || $uomId === null || $quantity <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Each transfer line requires item_id, uom_id and quantity > 0.',
            ));
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null || ! (bool) $item->get('is_stockable', false)) {
            return Result::failure(new Error(
                InventoryErrorCode::NON_STOCKABLE_ITEM,
                'Transfer lines must reference stockable items in the current tenant.',
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
     * @param array<string, mixed> $header
     * @param array<string, mixed> $line
     * @return Result<array<string, mixed>>
     */
    private function normalizeTransferLine(int $tenantId, array $header, array $line): Result
    {
        $itemId = (int) $line['item_id'];
        $uomId = (int) $line['uom_id'];
        $quantity = (float) $line['quantity'];

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null) {
            return Result::failure(new Error(InventoryErrorCode::ITEM_NOT_FOUND, 'Item not found for tenant.'));
        }

        $baseQuantity = $this->uomConversionService->convert(
            $quantity,
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
            'item_id' => $itemId,
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'from_location_id' => $line['from_location_id']
                ?? $line['location_id']
                ?? $header['from_location_id']
                ?? null,
            'to_location_id' => $line['to_location_id'] ?? $header['to_location_id'] ?? null,
            'uom_id' => $uomId,
            'quantity' => $quantity,
            'base_quantity' => (float) $baseQuantity->valueOrFail(),
            'unit_cost' => $line['unit_cost'] ?? null,
            'notes' => $line['notes'] ?? null,
        ]);
    }


    private function postTransferRecord(DataRecord $transfer): Result
    {
        $lines = $this->transferLineRepository->list([
            'stock_transfer_id' => $transfer->id(),
        ]);

        foreach ($lines as $line) {
            if ($line->get('outgoing_movement_id') !== null || $line->get('incoming_movement_id') !== null) {
                continue;
            }

            $quantity = (float) $line->get('quantity', 0);
            if ($quantity <= 0) {
                continue;
            }

            $performedAt = $transfer->get('transferred_at') ?? now();

            $outgoing = $this->stockLedgerService->recordMovement([
                'tenant_id' => (int) $transfer->get('tenant_id'),
                'organization_unit_id' => $transfer->get('organization_unit_id'),
                'item_id' => (int) $line->get('item_id'),
                'variant_id' => $line->get('variant_id'),
                'warehouse_id' => (int) $transfer->get('from_warehouse_id'),
                'location_id' => $line->get('from_location_id') ?? $transfer->get('from_location_id'),
                'batch_id' => $line->get('batch_id'),
                'serial_id' => $line->get('serial_id'),
                'uom_id' => (int) $line->get('uom_id'),
                'quantity' => $quantity,
                'direction' => 'OUT',
                'movement_type' => 'STOCK_TRANSFER_OUT',
                'performed_at' => $performedAt,
                'approved_by' => $transfer->get('approved_by'),
                'unit_cost' => $line->get('unit_cost'),
                'notes' => $line->get('notes') ?? $transfer->get('notes'),
                'source_type' => 'stock_transfer',
                'source_id' => $transfer->id(),
                'source_line_id' => $line->id(),
            ]);

            if ($outgoing->isFailure()) {
                return $outgoing;
            }

            $incoming = $this->stockLedgerService->recordMovement([
                'tenant_id' => (int) $transfer->get('tenant_id'),
                'organization_unit_id' => $transfer->get('organization_unit_id'),
                'item_id' => (int) $line->get('item_id'),
                'variant_id' => $line->get('variant_id'),
                'warehouse_id' => (int) $transfer->get('to_warehouse_id'),
                'location_id' => $line->get('to_location_id') ?? $transfer->get('to_location_id'),
                'batch_id' => $line->get('batch_id'),
                'serial_id' => $line->get('serial_id'),
                'uom_id' => (int) $line->get('uom_id'),
                'quantity' => $quantity,
                'direction' => 'IN',
                'movement_type' => 'STOCK_TRANSFER_IN',
                'performed_at' => $performedAt,
                'approved_by' => $transfer->get('approved_by'),
                'unit_cost' => $line->get('unit_cost'),
                'notes' => $line->get('notes') ?? $transfer->get('notes'),
                'source_type' => 'stock_transfer',
                'source_id' => $transfer->id(),
                'source_line_id' => $line->id(),
            ]);

            if ($incoming->isFailure()) {
                return $incoming;
            }

            $this->transferLineRepository->update($line->id(), [
                'outgoing_movement_id' => $outgoing->valueOrFail()->id(),
                'incoming_movement_id' => $incoming->valueOrFail()->id(),
            ]);
        }

        return Result::success($this->transferRepository->update($transfer->id(), [
            'status' => 'COMPLETED',
            'transferred_at' => $transfer->get('transferred_at') ?? now(),
        ]));
    }

    private function shouldPost(mixed $status): bool
    {
        return in_array(strtoupper((string) $status), ['COMPLETED', 'POSTED'], true);
    }

    private function isPosted(DataRecord $transfer): bool
    {
        if ($this->shouldPost($transfer->get('status'))) {
            return true;
        }

        $lines = $this->transferLineRepository->list([
            'stock_transfer_id' => $transfer->id(),
        ]);

        foreach ($lines as $line) {
            if ($line->get('outgoing_movement_id') !== null || $line->get('incoming_movement_id') !== null) {
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
        foreach (['from_warehouse_id', 'to_warehouse_id', 'from_location_id', 'to_location_id', 'lines'] as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
