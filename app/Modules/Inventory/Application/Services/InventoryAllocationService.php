<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\InventoryEngines\InventoryStrategyRegistryInterface;
use Modules\Inventory\Application\Contracts\Services\InventoryAllocationServiceInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Services\InventoryEngines\InventoryEngineContextFactory;
use Modules\Inventory\Application\Services\InventoryEngines\InventoryEnginePolicyPipeline;
use Modules\Inventory\Domain\Constants\InventoryEngineKey;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class InventoryAllocationService implements InventoryAllocationServiceInterface
{
    public function __construct(
        private readonly InventoryEngineContextFactory $contextFactory,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly InventoryStrategyRegistryInterface $strategyRegistry,
        private readonly InventoryEnginePolicyPipeline $policyPipeline,
    ) {}

    public function allocate(array $payload): Result
    {
        try {
            $contextResult = $this->contextFactory->create(InventoryEngineKey::ALLOCATION, $payload);
            if ($contextResult->isFailure()) {
                return $contextResult;
            }

            $context = (array) $contextResult->valueOrFail();
            $method = $this->contextFactory->requestedMethod(InventoryEngineKey::ALLOCATION, $payload);
            if ($method === '') {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_STRATEGY,
                    'An allocation method must be supplied or configured.',
                ));
            }

            $strategyResult = $this->strategyRegistry->resolveAllocation($method);
            if ($strategyResult->isFailure()) {
                return $strategyResult;
            }

            $strategy = $strategyResult->valueOrFail();
            $criteria = $this->contextFactory->criteria(
                $context,
                $this->contextFactory->dimensionFields('inventory.engines.allocation.criteria_dimensions'),
            );

            $stockLevels = $this->stockLevelRepository->listAllocatableStock(
                $criteria,
                $method,
                $this->sourceLimit(InventoryEngineKey::STOCK_LEVELS, 'max_stock_fetch', 1000),
            );

            $strategyContext = array_replace($context, [
                InventoryEngineKey::METHOD => $method,
                InventoryEngineKey::STRATEGY_METHOD => $strategy->method(),
                InventoryEngineKey::METHOD_OPTIONS => $this->contextFactory->methodOptions(InventoryEngineKey::ALLOCATION, $method),
                InventoryEngineKey::STOCK_LEVELS => $this->recordsToArrays($stockLevels),
            ]);

            $policyResult = $this->policyPipeline->inspect(InventoryEngineKey::ALLOCATION, $strategyContext);
            if ($policyResult->isFailure()) {
                return $policyResult;
            }

            $allocation = $strategy->allocate($strategyContext);

            if (((float) ($allocation[InventoryEngineKey::ALLOCATED_QUANTITY] ?? 0)) <= 0.0) {
                return Result::failure(new Error(
                    InventoryErrorCode::INSUFFICIENT_STOCK,
                    'No allocatable stock was found for the requested dimensions.',
                    [
                        InventoryEngineKey::DIMENSIONS => $strategyContext[InventoryEngineKey::DIMENSIONS],
                        InventoryEngineKey::METHOD => $method,
                    ],
                ));
            }

            return Result::success([
                InventoryEngineKey::METHOD => $method,
                InventoryEngineKey::STRATEGY_METHOD => $strategy->method(),
                InventoryEngineKey::DIMENSIONS => $strategyContext[InventoryEngineKey::DIMENSIONS],
                InventoryEngineKey::EXTRA_DIMENSIONS => $strategyContext[InventoryEngineKey::EXTRA_DIMENSIONS],
                InventoryEngineKey::SPECIFICITY_ORDER => $strategyContext[InventoryEngineKey::SPECIFICITY_ORDER],
                InventoryEngineKey::RESULT => $allocation,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function sourceLimit(string $source, string $legacyKey, int $default): int
    {
        $value = config(
            sprintf('inventory.engines.allocation.source_limits.%s', $source),
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
