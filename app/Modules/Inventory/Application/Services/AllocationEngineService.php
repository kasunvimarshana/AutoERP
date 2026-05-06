<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\AllocationEngineServiceInterface;
use Modules\Inventory\Application\DTOs\AllocationRequestData;
use Modules\Inventory\Application\Strategies\Allocation\FefoAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\FifoAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\LifoAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\ManualAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\NearestBinAllocationStrategy;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\RepositoryInterfaces\CostLayerRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;

/**
 * Orchestrates stock allocation: resolves the strategy, fetches open layers
 * in the correct order, and delegates to the AllocationStrategyInterface.
 */
class AllocationEngineService implements AllocationEngineServiceInterface
{
    /** @var array<string, AllocationStrategyInterface> */
    private array $strategies;

    public function __construct(
        private readonly CostLayerRepositoryInterface $costLayerRepository,
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
    ) {
        $this->strategies = [
            'fifo' => new FifoAllocationStrategy,
            'lifo' => new LifoAllocationStrategy,
            'fefo' => new FefoAllocationStrategy,
            'nearest_bin' => new NearestBinAllocationStrategy,
            'manual' => new ManualAllocationStrategy,
        ];
    }

    public function allocate(AllocationRequestData $request): AllocationResult
    {
        $strategy = $this->resolveStrategyByName($request->allocationStrategy);

        $layers = match ($request->allocationStrategy) {
            'lifo' => $this->costLayerRepository->findOpenLayersNewestFirst(
                $request->tenantId, $request->productId, $request->locationId, $request->variantId,
            ),
            'fefo' => $this->costLayerRepository->findOpenLayersByExpiryAsc(
                $request->tenantId, $request->productId, $request->locationId, $request->variantId,
            ),
            default => $this->costLayerRepository->findOpenLayersOldestFirst(
                $request->tenantId, $request->productId, $request->locationId, $request->variantId,
            ),
        };

        return $strategy->allocate($request->requiredQuantity, $layers, $request->context);
    }

    public function resolveAllocationStrategy(
        int $tenantId,
        ?int $productId = null,
        ?int $warehouseId = null,
        ?int $orgUnitId = null,
        ?string $transactionType = null,
    ): string {
        $config = $this->valuationConfigRepository->resolveEffective(
            $tenantId,
            $productId,
            $warehouseId,
            $orgUnitId,
            $transactionType,
        );

        return $config?->getAllocationStrategy() ?? 'fifo';
    }

    private function resolveStrategyByName(string $strategy): AllocationStrategyInterface
    {
        if (! isset($this->strategies[$strategy])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown allocation strategy "%s". Supported: %s.', $strategy, implode(', ', array_keys($this->strategies))),
            );
        }

        return $this->strategies[$strategy];
    }
}
