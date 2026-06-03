<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryStrategyRegistryInterface;
use Modules\Inventory\Application\Contracts\Services\InventoryValuationServiceInterface;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Application\Services\InventoryEngines\InventoryEngineContextFactory;
use Modules\Inventory\Application\Services\InventoryEngines\InventoryEnginePolicyPipeline;
use Modules\Inventory\Domain\Constants\InventoryEngineKey;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class InventoryValuationService implements InventoryValuationServiceInterface
{
    public function __construct(
        private readonly InventoryEngineContextFactory $contextFactory,
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
        private readonly InventoryCostLayerRepositoryInterface $inventoryCostLayerRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly InventoryStrategyRegistryInterface $strategyRegistry,
        private readonly InventoryEnginePolicyPipeline $policyPipeline,
    ) {}

    public function value(array $payload): Result
    {
        try {
            $contextResult = $this->contextFactory->create(InventoryEngineKey::VALUATION, $payload);
            if ($contextResult->isFailure()) {
                return $contextResult;
            }

            $context = (array) $contextResult->valueOrFail();
            $configuration = $this->valuationConfiguration($context);
            $configuredMethod = $configuration?->get('valuation_method');
            $method = $this->contextFactory->requestedMethod(
                InventoryEngineKey::VALUATION,
                $payload,
                is_string($configuredMethod) ? $configuredMethod : null,
            );

            if ($method === '') {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    'A valuation method must be supplied or configured.',
                ));
            }

            $strategyResult = $this->strategyRegistry->resolveValuation($method);
            if ($strategyResult->isFailure()) {
                return $strategyResult;
            }

            $strategy = $strategyResult->valueOrFail();
            $criteria = $this->contextFactory->criteria(
                $context,
                $this->contextFactory->dimensionFields('inventory.engines.valuation.criteria_dimensions'),
            );

            $layers = $this->inventoryCostLayerRepository->listOpenLayers(
                $criteria,
                $method,
                $this->sourceLimit(InventoryEngineKey::LAYERS, 'max_layer_fetch', 1000),
            );
            $stockLevels = $this->stockLevelRepository->listAllocatableStock(
                $criteria,
                (string) config('inventory.engines.allocation.default_method', config('inventory.engines.default_allocation_method', 'fifo')),
                $this->sourceLimit(InventoryEngineKey::STOCK_LEVELS, 'max_stock_fetch', 1000),
            );

            $strategyContext = array_replace($context, [
                InventoryEngineKey::METHOD => $method,
                InventoryEngineKey::STRATEGY_METHOD => $strategy->method(),
                InventoryEngineKey::METHOD_OPTIONS => $this->contextFactory->methodOptions(InventoryEngineKey::VALUATION, $method),
                InventoryEngineKey::LAYERS => $this->recordsToArrays($layers),
                InventoryEngineKey::STOCK_LEVELS => $this->recordsToArrays($stockLevels),
                InventoryEngineKey::STANDARD_COST => $payload[InventoryEngineKey::STANDARD_COST] ?? null,
                'configuration' => $configuration?->toArray(),
            ]);

            $policyResult = $this->policyPipeline->inspect(InventoryEngineKey::VALUATION, $strategyContext);
            if ($policyResult->isFailure()) {
                return $policyResult;
            }

            $calculated = $strategy->calculate($strategyContext);

            return Result::success([
                InventoryEngineKey::METHOD => $method,
                InventoryEngineKey::STRATEGY_METHOD => $strategy->method(),
                InventoryEngineKey::DIMENSIONS => $strategyContext[InventoryEngineKey::DIMENSIONS],
                InventoryEngineKey::EXTRA_DIMENSIONS => $strategyContext[InventoryEngineKey::EXTRA_DIMENSIONS],
                InventoryEngineKey::SPECIFICITY_ORDER => $strategyContext[InventoryEngineKey::SPECIFICITY_ORDER],
                InventoryEngineKey::CONFIGURATION_ID => $configuration?->get('id'),
                InventoryEngineKey::CONFIGURATION_ROW_VERSION => $configuration?->get('row_version'),
                InventoryEngineKey::RESULT => $calculated,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function valuationConfiguration(array $context): ?DataRecord
    {
        $criteria = $this->contextFactory->criteria(
            $context,
            $this->contextFactory->dimensionFields('inventory.engines.valuation.configuration_dimensions'),
            true,
        );
        $criteria[InventoryEngineKey::TRANSACTION_TYPE] = $context[InventoryEngineKey::TRANSACTION_TYPE] ?? null;

        return $this->valuationConfigRepository->findActiveForDimensions($criteria);
    }

    private function sourceLimit(string $source, string $legacyKey, int $default): int
    {
        $value = config(
            sprintf('inventory.engines.valuation.source_limits.%s', $source),
            config(sprintf('inventory.engines.%s', $legacyKey), $default),
        );

        return is_int($value) && $value > 0 ? $value : $default;
    }

    /**
     * @param  list<DataRecord>  $records
     * @return list<array<string, mixed>>
     */
    private function recordsToArrays(array $records): array
    {
        $items = [];
        foreach ($records as $record) {
            if ($record instanceof DataRecord) {
                $items[] = $record->toArray();
            }
        }

        return $items;
    }
}
