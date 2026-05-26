<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\AllocateInventoryStockServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\ResolveInventoryDimensionsServiceInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;

final class AllocateInventoryStockService implements AllocateInventoryStockServiceInterface
{
    public function __construct(
        private readonly ResolveInventoryDimensionsServiceInterface $dimensionResolver,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ResolveAllocationStrategyService $strategyResolver,
    ) {
    }

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $payload[InventoryDimension::TENANT_ID] ?? null;
            $itemId = $payload[InventoryDimension::ITEM_ID] ?? null;
            $requestedQuantity = (float) ($payload['quantity'] ?? 0);

            if ($tenantId === null || $itemId === null || $requestedQuantity <= 0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'tenant_id, item_id and quantity (> 0) are required for allocation.',
                ));
            }

            $resolvedDimensionsResult = $this->dimensionResolver->execute($payload);
            if ($resolvedDimensionsResult->isFailure()) {
                return $resolvedDimensionsResult;
            }

            $resolved = (array) $resolvedDimensionsResult->valueOrFail();
            $dimensions = (array) ($resolved['dimensions'] ?? []);

            $method = strtolower((string) (
                $payload['allocation_method']
                ?? config('inventory.engines.default_allocation_method', InventoryAllocationMethod::FIFO)
            ));

            $strategyResult = $this->strategyResolver->execute($method);
            if ($strategyResult->isFailure()) {
                return $strategyResult;
            }

            $strategy = $strategyResult->valueOrFail();

            $criteria = [
                InventoryDimension::TENANT_ID => $tenantId,
                InventoryDimension::ITEM_ID => $itemId,
                InventoryDimension::ORGANIZATION_UNIT_ID =>
                    $dimensions[InventoryDimension::ORGANIZATION_UNIT_ID] ?? null,
                InventoryDimension::WAREHOUSE_ID => $dimensions[InventoryDimension::WAREHOUSE_ID] ?? null,
                InventoryDimension::LOCATION_ID => $dimensions[InventoryDimension::LOCATION_ID] ?? null,
                InventoryDimension::VARIANT_ID => $dimensions[InventoryDimension::VARIANT_ID] ?? null,
                InventoryDimension::BATCH_ID => $dimensions[InventoryDimension::BATCH_ID] ?? null,
                InventoryDimension::LOT_NUMBER => $dimensions[InventoryDimension::LOT_NUMBER] ?? null,
                InventoryDimension::SERIAL_ID => $dimensions[InventoryDimension::SERIAL_ID] ?? null,
            ];

            $stockLevels = $this->stockLevelRepository->listAllocatableStock(
                $criteria,
                $method,
                (int) config('inventory.engines.max_stock_fetch', 1000),
            );

            $stockLevelArrays = array_map(
                static fn (DataRecord $record): array => $record->toArray(),
                $stockLevels,
            );

            $allocation = $strategy->allocate([
                'requested_quantity' => $requestedQuantity,
                'stock_levels' => $stockLevelArrays,
                'dimensions' => $dimensions,
            ]);

            if (((float) ($allocation['allocated_quantity'] ?? 0)) <= 0.0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INSUFFICIENT_STOCK,
                    'No allocatable stock was found for the requested dimensions.',
                    [
                        'dimensions' => $dimensions,
                    ],
                ));
            }

            return Result::success([
                'method' => $strategy->method(),
                'dimensions' => $dimensions,
                'extra_dimensions' => $resolved['extra_dimensions'] ?? [],
                'specificity_order' => $resolved['specificity_order'] ?? [],
                'result' => $allocation,
            ]);
        } catch (\Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
