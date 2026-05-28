<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\InventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class InventoryCostLayerService implements InventoryCostLayerServiceInterface
{
    public function __construct(private readonly InventoryCostLayerRepositoryInterface $inventoryCostLayerRepository)
    {
    }

    public function createLayer(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;
            $payload['is_closed'] ??= false;

            return Result::success($this->inventoryCostLayerRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateLayer(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->inventoryCostLayerRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'InventoryCostLayer not found.'));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload), false);
            if ($validation->isFailure()) {
                return $validation;
            }

            if ($this->isClosed($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Closed inventory cost layers are immutable.',
                ));
            }

            return Result::success($this->inventoryCostLayerRepository->update($id, $payload));
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
        $layerDate = $payload['layer_date'] ?? null;
        $quantityIn = (float) ($payload['quantity_in'] ?? 0);
        $quantityRemaining = (float) ($payload['quantity_remaining'] ?? 0);
        $unitCost = (float) ($payload['unit_cost'] ?? 0);

        if (
            $tenantId === null
            || $itemId === null
            || $layerDate === null
            || $quantityIn <= 0
            || $quantityRemaining < 0
            || $unitCost < 0
        ) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id, layer_date, quantity_in (> 0), '
                . 'quantity_remaining (>= 0) and unit_cost (>= 0) are required.',
            ));
        }

        if ($quantityRemaining > $quantityIn) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'quantity_remaining cannot exceed quantity_in.',
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
                'batch_id',
                'serial_id',
                'warehouse_id',
                'location_id',
                'valuation_method',
                'layer_date',
                'quantity_in',
                'quantity_remaining',
                'unit_cost',
                'reference_type',
                'reference_id',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isClosed(DataRecord $layer): bool
    {
        return (bool) $layer->get('is_closed', false);
    }
}
