<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\CalculateInventoryValuationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryEngines\ResolveInventoryDimensionsServiceInterface;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class CalculateInventoryValuationService implements CalculateInventoryValuationServiceInterface
{
    public function __construct(
        private readonly ResolveInventoryDimensionsServiceInterface $dimensionResolver,
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
        private readonly InventoryCostLayerRepositoryInterface $inventoryCostLayerRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly ResolveValuationStrategyService $strategyResolver,
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
                    'tenant_id, item_id and quantity (> 0) are required for valuation.',
                ));
            }

            $resolvedDimensionsResult = $this->dimensionResolver->execute($payload);
            if ($resolvedDimensionsResult->isFailure()) {
                return $resolvedDimensionsResult;
            }

            $resolved = (array) $resolvedDimensionsResult->valueOrFail();
            $dimensions = (array) ($resolved['dimensions'] ?? []);
            $transactionType = $resolved['transaction_type'] ?? null;

            $configCriteria = [
                InventoryDimension::TENANT_ID => $tenantId,
                InventoryDimension::ORGANIZATION_UNIT_ID =>
                    $dimensions[InventoryDimension::ORGANIZATION_UNIT_ID] ?? null,
                InventoryDimension::WAREHOUSE_ID => $dimensions[InventoryDimension::WAREHOUSE_ID] ?? null,
                InventoryDimension::LOCATION_ID => $dimensions[InventoryDimension::LOCATION_ID] ?? null,
                InventoryDimension::ITEM_ID => $itemId,
                InventoryDimension::VARIANT_ID => $dimensions[InventoryDimension::VARIANT_ID] ?? null,
                InventoryDimension::BATCH_ID => $dimensions[InventoryDimension::BATCH_ID] ?? null,
                InventoryDimension::SERIAL_ID => $dimensions[InventoryDimension::SERIAL_ID] ?? null,
                'transaction_type' => $transactionType,
            ];

            $valuationConfig = $this->valuationConfigRepository->findActiveForDimensions($configCriteria);
            $configuredMethod = $valuationConfig?->get('valuation_method');
            $method = strtolower((string) (
                $payload['valuation_method']
                ?? $configuredMethod
                ?? config('inventory.engines.default_valuation_method', InventoryValuationMethod::WEIGHTED_AVERAGE)
            ));

            $strategyResult = $this->strategyResolver->execute($method);
            if ($strategyResult->isFailure()) {
                return $strategyResult;
            }

            $strategy = $strategyResult->valueOrFail();

            $layerCriteria = [
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

            $layers = $this->inventoryCostLayerRepository->listOpenLayers(
                $layerCriteria,
                $method,
                (int) config('inventory.engines.max_layer_fetch', 1000),
            );
            $stockLevels = $this->stockLevelRepository->listAllocatableStock(
                $layerCriteria,
                config('inventory.engines.default_allocation_method', 'fifo'),
                (int) config('inventory.engines.max_stock_fetch', 1000),
            );

            $layerArrays = array_map(
                static fn (DataRecord $record): array => $record->toArray(),
                $layers,
            );
            $stockLevelArrays = array_map(
                static fn (DataRecord $record): array => $record->toArray(),
                $stockLevels,
            );

            $calculated = $strategy->calculate([
                'requested_quantity' => $requestedQuantity,
                'layers' => $layerArrays,
                'stock_levels' => $stockLevelArrays,
                'standard_cost' => $payload['standard_cost'] ?? null,
                'dimensions' => $dimensions,
            ]);

            return Result::success([
                'method' => $strategy->method(),
                'dimensions' => $dimensions,
                'extra_dimensions' => $resolved['extra_dimensions'] ?? [],
                'specificity_order' => $resolved['specificity_order'] ?? [],
                'configuration_id' => $valuationConfig?->get('id'),
                'configuration_row_version' => $valuationConfig?->get('row_version'),
                'result' => $calculated,
            ]);
        } catch (\Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
