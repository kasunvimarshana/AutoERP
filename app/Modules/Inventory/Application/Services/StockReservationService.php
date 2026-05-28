<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockReservationServiceInterface;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Throwable;

final class StockReservationService implements StockReservationServiceInterface
{
    public function __construct(
        private readonly StockReservationRepositoryInterface $stockReservationRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly SerialRepositoryInterface $serialRepository,
    ) {
    }

    public function reserve(array $payload): Result
    {
        try {
            [$tenantId, $item, $uomId, $baseUomId, $quantity, $baseQuantity, $warehouseId] =
                $this->resolveReservationContext($payload);

            if (
                $tenantId === null
                || $item === null
                || $uomId === null
                || $baseUomId === null
                || $warehouseId === null
            ) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id, item_id, warehouse_id, uom_id and quantity are required.',
                ));
            }

            $levelCriteria = $this->stockLevelCriteria(
                $payload,
                $tenantId,
                (int) $item->id(),
                $baseUomId,
                $warehouseId,
            );
            $level = $this->findMatchingStockLevel($levelCriteria);

            if ($level === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::INSUFFICIENT_STOCK,
                    'No stock level matched the requested reservation scope.',
                ));
            }

            $availableQuantity = $this->availableQuantity($level);
            if ($availableQuantity < $baseQuantity) {
                return Result::failure(new Error(
                    InventoryErrorCode::INSUFFICIENT_STOCK,
                    'Available stock is insufficient for the requested reservation quantity.',
                    ['available_quantity' => $availableQuantity, 'requested_quantity' => $baseQuantity],
                ));
            }

            return $this->stockReservationRepository->transaction(function () use (
                $payload,
                $tenantId,
                $item,
                $uomId,
                $baseUomId,
                $quantity,
                $baseQuantity,
                $level,
            ): Result {
                $reservation = $this->stockReservationRepository->create([
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'item_id' => (int) $item->id(),
                    'variant_id' => $payload['variant_id'] ?? null,
                    'batch_id' => $payload['batch_id'] ?? null,
                    'serial_id' => $payload['serial_id'] ?? null,
                    'warehouse_id' => $payload['warehouse_id'],
                    'location_id' => $payload['location_id'] ?? null,
                    'transaction_uom_id' => $uomId,
                    'base_uom_id' => $baseUomId,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'quantity_consumed' => 0,
                    'quantity_released' => 0,
                    'status' => 'ACTIVE',
                    'reserved_for_type' => $payload['reserved_for_type'] ?? null,
                    'reserved_for_id' => $payload['reserved_for_id'] ?? null,
                    'expires_at' => $payload['expires_at'] ?? null,
                    'unit_cost' => $payload['unit_cost'] ?? $level->get('unit_cost'),
                    'reserved_by' => $payload['reserved_by'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]);

                $this->stockLevelRepository->update($level->id(), [
                    'quantity_reserved' => round((float) $level->get('quantity_reserved', 0) + $baseQuantity, 4),
                ]);

                return Result::success($reservation);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function release(int|string $reservationId, array $payload = []): Result
    {
        return $this->adjustReservation($reservationId, $payload, 'release');
    }

    public function consume(int|string $reservationId, array $payload = []): Result
    {
        return $this->adjustReservation($reservationId, $payload, 'consume');
    }

    /**
     * @return array{0:?int,1:?DataRecord,2:?int,3:?int,4:float,5:float,6:?int}
     */
    private function resolveReservationContext(array $payload): array
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $uomId = isset($payload['uom_id']) ? (int) $payload['uom_id'] : null;
        $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;
        $quantity = (float) ($payload['quantity'] ?? 0);

        if ($tenantId === null || $itemId === null || $uomId === null || $warehouseId === null || $quantity <= 0) {
            return [null, null, null, null, 0.0, 0.0, null];
        }

        $item = $this->itemRepository->findByIdInTenant($itemId, $tenantId);
        if ($item === null) {
            throw new \InvalidArgumentException('Item not found for tenant.');
        }

        if (! (bool) $item->get('is_stockable', false)) {
            throw new \InvalidArgumentException('Only stockable items can reserve stock.');
        }

        $baseUomId = (int) $item->get('base_uom_id');
        $conversion = $this->uomConversionService->convert($quantity, $uomId, $baseUomId, $tenantId, $itemId);
        if ($conversion->isFailure()) {
            throw new \InvalidArgumentException($conversion->errorOrFail()->message);
        }

        $baseQuantity = (float) $conversion->valueOrFail();
        $this->assertBatchAndSerialScope(
            $payload,
            $tenantId,
            $itemId,
            $baseQuantity,
            (bool) $item->get('is_serial_tracked', false),
        );

        return [$tenantId, $item, $uomId, $baseUomId, $quantity, $baseQuantity, $warehouseId];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $criteria
     */
    private function stockLevelCriteria(
        array $payload,
        int $tenantId,
        int $itemId,
        int $baseUomId,
        int $warehouseId,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'item_id' => $itemId,
            'variant_id' => $payload['variant_id'] ?? null,
            'warehouse_id' => $warehouseId,
            'location_id' => $payload['location_id'] ?? null,
            'batch_id' => $payload['batch_id'] ?? null,
            'serial_id' => $payload['serial_id'] ?? null,
            'base_uom_id' => $baseUomId,
            'condition' => $payload['condition'] ?? 'good',
        ];
    }

    private function adjustReservation(int|string $reservationId, array $payload, string $mode): Result
    {
        try {
            $reservation = $this->stockReservationRepository->findById($reservationId);
            if ($reservation === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::RESERVATION_NOT_FOUND,
                    'Stock reservation not found.',
                ));
            }

            $status = strtoupper((string) $reservation->get('status', 'ACTIVE'));
            if (! in_array($status, ['ACTIVE', 'PARTIALLY_CONSUMED'], true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_RESERVATION_STATUS,
                    'Only active or partially consumed reservations can be adjusted.',
                ));
            }

            $remaining = round(
                (float) $reservation->get('base_quantity', 0)
                - (float) $reservation->get('quantity_consumed', 0)
                - (float) $reservation->get('quantity_released', 0),
                4,
            );

            $requestedBaseQuantity = isset($payload['quantity'])
                ? $this->convertReservationQuantity($reservation, (float) $payload['quantity'])
                : $remaining;

            if ($requestedBaseQuantity <= 0 || $requestedBaseQuantity > $remaining) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_RESERVATION_QUANTITY,
                    'Reservation adjustment quantity exceeds the remaining reserved stock.',
                ));
            }

            $level = $this->findMatchingStockLevel([
                'tenant_id' => $reservation->get('tenant_id'),
                'organization_unit_id' => $reservation->get('organization_unit_id'),
                'item_id' => $reservation->get('item_id'),
                'variant_id' => $reservation->get('variant_id'),
                'warehouse_id' => $reservation->get('warehouse_id'),
                'location_id' => $reservation->get('location_id'),
                'batch_id' => $reservation->get('batch_id'),
                'serial_id' => $reservation->get('serial_id'),
                'base_uom_id' => $reservation->get('base_uom_id'),
                'condition' => 'good',
            ]);

            if ($level === null) {
                return Result::failure(new Error(
                    InventoryErrorCode::NOT_FOUND,
                    'Matching stock level was not found for the reservation.',
                ));
            }

            return $this->stockReservationRepository->transaction(function () use (
                $reservation,
                $payload,
                $mode,
                $requestedBaseQuantity,
                $remaining,
                $level,
            ): Result {
                $quantityConsumed = (float) $reservation->get('quantity_consumed', 0);
                $quantityReleased = (float) $reservation->get('quantity_released', 0);

                $reservationUpdate = [];
                if ($mode === 'consume') {
                    $reservationUpdate['quantity_consumed'] = round($quantityConsumed + $requestedBaseQuantity, 4);
                    $reservationUpdate['consumed_by'] = $payload['consumed_by'] ?? null;
                    $reservationUpdate['consumed_at'] = $payload['consumed_at'] ?? now();
                } else {
                    $reservationUpdate['quantity_released'] = round($quantityReleased + $requestedBaseQuantity, 4);
                    $reservationUpdate['released_by'] = $payload['released_by'] ?? null;
                    $reservationUpdate['released_at'] = $payload['released_at'] ?? now();
                }

                $remainingAfter = round($remaining - $requestedBaseQuantity, 4);
                $reservationUpdate['status'] = $remainingAfter <= 0
                    ? ($mode === 'consume' ? 'CONSUMED' : 'RELEASED')
                    : 'PARTIALLY_CONSUMED';

                $updatedReservation = $this->stockReservationRepository->update($reservation->id(), $reservationUpdate);

                $this->stockLevelRepository->update($level->id(), [
                    'quantity_reserved' => round(
                        (float) $level->get('quantity_reserved', 0) - $requestedBaseQuantity,
                        4,
                    ),
                ]);

                return Result::success($updatedReservation);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function convertReservationQuantity(DataRecord $reservation, float $quantity): float
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        $conversion = $this->uomConversionService->convert(
            $quantity,
            (int) $reservation->get('transaction_uom_id'),
            (int) $reservation->get('base_uom_id'),
            (int) $reservation->get('tenant_id'),
            (int) $reservation->get('item_id'),
        );

        if ($conversion->isFailure()) {
            throw new \InvalidArgumentException($conversion->errorOrFail()->message);
        }

        return (float) $conversion->valueOrFail();
    }

    private function availableQuantity(DataRecord $level): float
    {
        return round(
            (float) $level->get('quantity_on_hand', 0)
            - (float) $level->get('quantity_reserved', 0)
            - (float) $level->get('quantity_blocked', 0),
            4,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertBatchAndSerialScope(
        array $payload,
        int $tenantId,
        int $itemId,
        float $baseQuantity,
        bool $isSerialTracked,
    ): void {
        $batchId = isset($payload['batch_id']) ? (int) $payload['batch_id'] : null;
        if ($batchId !== null) {
            $batch = $this->batchRepository->findById($batchId);
            if (
                $batch === null
                || (int) $batch->get('tenant_id') !== $tenantId
                || (int) $batch->get('item_id') !== $itemId
            ) {
                throw new \InvalidArgumentException('batch_id must belong to the same tenant and item.');
            }
        }

        $serialId = isset($payload['serial_id']) ? (int) $payload['serial_id'] : null;
        if ($isSerialTracked && $serialId === null) {
            throw new \InvalidArgumentException('Serialized items require serial_id for reservations.');
        }

        if ($serialId !== null) {
            $serial = $this->serialRepository->findById($serialId);
            if (
                $serial === null
                || (int) $serial->get('tenant_id') !== $tenantId
                || (int) $serial->get('item_id') !== $itemId
            ) {
                throw new \InvalidArgumentException('serial_id must belong to the same tenant and item.');
            }

            if ($baseQuantity !== 1.0) {
                throw new \InvalidArgumentException(
                    'Serialized reservations must use a quantity of exactly 1 in base units.',
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function findMatchingStockLevel(array $criteria): ?DataRecord
    {
        $matches = $this->stockLevelRepository->list($criteria);

        return $matches[0] ?? null;
    }
}
