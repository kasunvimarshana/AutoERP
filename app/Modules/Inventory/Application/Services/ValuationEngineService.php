<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ValuationEngineServiceInterface;
use Modules\Inventory\Application\DTOs\CostLayerInboundData;
use Modules\Inventory\Application\Strategies\Valuation\FefoValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\FifoValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\LifoValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\SpecificIdentificationValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\StandardCostValuationStrategy;
use Modules\Inventory\Application\Strategies\Valuation\WeightedAverageValuationStrategy;
use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\CostLayerRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;

/**
 * Orchestrates valuation: resolves the strategy, fetches/updates cost layers,
 * and delegates computation to the active ValuationStrategyInterface.
 */
class ValuationEngineService implements ValuationEngineServiceInterface
{
    /** @var array<string, ValuationStrategyInterface> */
    private array $strategies;

    public function __construct(
        private readonly CostLayerRepositoryInterface $costLayerRepository,
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
    ) {
        $this->strategies = [
            'fifo' => new FifoValuationStrategy,
            'lifo' => new LifoValuationStrategy,
            'fefo' => new FefoValuationStrategy,
            'weighted_average' => new WeightedAverageValuationStrategy,
            'standard' => new StandardCostValuationStrategy,
            'specific' => new SpecificIdentificationValuationStrategy,
        ];
    }

    public function processInbound(CostLayerInboundData $dto): InventoryCostLayer
    {
        $strategy = $this->resolveStrategy($dto->valuationMethod);

        $context = [
            'tenant_id' => $dto->tenantId,
            'product_id' => $dto->productId,
            'variant_id' => $dto->variantId,
            'batch_id' => $dto->batchId,
            'location_id' => $dto->locationId,
            'layer_date' => $dto->layerDate,
            'quantity' => $dto->quantity,
            'unit_cost' => $dto->unitCost,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
        ];

        $newLayer = $strategy->buildInboundLayer($context);

        $existingLayers = $this->costLayerRepository->findAllOpenLayers(
            $dto->tenantId,
            $dto->productId,
            $dto->locationId,
            $dto->variantId,
        );

        $resultLayer = $strategy->recalculateOnReceipt($newLayer, $existingLayers);

        if ($resultLayer->getId() !== null) {
            return $this->costLayerRepository->update($resultLayer);
        }

        return $this->costLayerRepository->create($resultLayer);
    }

    public function processOutbound(
        int $tenantId,
        int $productId,
        ?int $variantId,
        int $locationId,
        string $quantity,
        string $valuationMethod,
    ): array {
        $strategy = $this->resolveStrategy($valuationMethod);

        $layers = match ($valuationMethod) {
            'lifo' => $this->costLayerRepository->findOpenLayersNewestFirst($tenantId, $productId, $locationId, $variantId),
            'fefo' => $this->costLayerRepository->findOpenLayersByExpiryAsc($tenantId, $productId, $locationId, $variantId),
            default => $this->costLayerRepository->findOpenLayersOldestFirst($tenantId, $productId, $locationId, $variantId),
        };

        $touched = $strategy->consumeLayers($quantity, $layers);

        foreach ($touched as $layer) {
            $this->costLayerRepository->update($layer);
        }

        return $touched;
    }

    public function resolveValuationMethod(
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

        return $config?->getValuationMethod() ?? 'fifo';
    }

    private function resolveStrategy(string $method): ValuationStrategyInterface
    {
        if (! isset($this->strategies[$method])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown valuation method "%s". Supported: %s.', $method, implode(', ', array_keys($this->strategies))),
            );
        }

        return $this->strategies[$method];
    }
}
