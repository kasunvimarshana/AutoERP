<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockMovementServiceInterface;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class StockMovementService implements StockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $stockMovementRepository)
    {
    }

    public function updateMovement(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->stockMovementRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'StockMovement not found.'));
            }

            if ($this->isLocked($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Posted stock movements are immutable.',
                ));
            }

            return Result::success($this->stockMovementRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
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
                'direction',
                'movement_type',
                'item_id',
                'variant_id',
                'batch_id',
                'serial_id',
                'location_id',
                'warehouse_id',
                'source_type',
                'source_id',
                'source_line_id',
                'transaction_uom_id',
                'base_uom_id',
                'quantity',
                'base_quantity',
                'quantity_in',
                'quantity_out',
                'base_quantity_in',
                'base_quantity_out',
                'unit_cost',
                'total_cost',
                'balance_quantity',
                'balance_value',
                'performed_by',
                'approved_by',
                'reversed_movement_id',
                'performed_at',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isLocked(DataRecord $movement): bool
    {
        return in_array(strtoupper((string) $movement->get('status')), ['POSTED', 'REVERSED', 'CANCELLED'], true);
    }
}
