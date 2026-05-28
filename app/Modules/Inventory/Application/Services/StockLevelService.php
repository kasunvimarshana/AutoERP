<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLevelServiceInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class StockLevelService implements StockLevelServiceInterface
{
    public function __construct(private readonly StockLevelRepositoryInterface $stockLevelRepository)
    {
    }

    public function createLevel(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;
            $payload['quantity_on_hand'] ??= 0;
            $payload['quantity_reserved'] ??= 0;
            $payload['quantity_blocked'] ??= 0;
            $payload['quantity_damaged'] ??= 0;
            $payload['quantity_in_transit'] ??= 0;
            $payload['condition'] ??= 'good';

            return Result::success($this->stockLevelRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateLevel(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->stockLevelRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockLevel not found.'));
            }

            if ($this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Stock level dimension changes are not allowed.',
                ));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload), false);
            if ($validation->isFailure()) {
                return $validation;
            }

            return Result::success($this->stockLevelRepository->update($id, $payload));
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
        $warehouseId = isset($payload['warehouse_id']) ? (int) $payload['warehouse_id'] : null;
        $baseUomId = isset($payload['base_uom_id']) ? (int) $payload['base_uom_id'] : null;
        $quantityOnHand = (float) ($payload['quantity_on_hand'] ?? 0);
        $quantityReserved = (float) ($payload['quantity_reserved'] ?? 0);
        $quantityBlocked = (float) ($payload['quantity_blocked'] ?? 0);
        $quantityDamaged = (float) ($payload['quantity_damaged'] ?? 0);
        $quantityInTransit = (float) ($payload['quantity_in_transit'] ?? 0);

        if ($tenantId === null || $itemId === null || $warehouseId === null || $baseUomId === null) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id, warehouse_id and base_uom_id are required.',
            ));
        }

        foreach (
            [
                $quantityOnHand,
                $quantityReserved,
                $quantityBlocked,
                $quantityDamaged,
                $quantityInTransit,
            ] as $value
        ) {
            if ($value < 0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Stock level quantities cannot be negative.',
                ));
            }
        }

        if ($quantityReserved + $quantityBlocked + $quantityDamaged > $quantityOnHand) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'Reserved, blocked and damaged quantities cannot exceed quantity on hand.',
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
                'item_id',
                'variant_id',
                'warehouse_id',
                'location_id',
                'batch_id',
                'serial_id',
                'base_uom_id',
                'condition',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
