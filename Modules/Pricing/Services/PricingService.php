<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Illuminate\Support\Collection;
use Modules\Pricing\Contracts\PricingEngineInterface;
use Modules\Pricing\Enums\PricingStrategy;

/**
 * PricingService
 *
 * Registry and orchestrator for pricing engines
 */
class PricingService
{
    /**
     * @var Collection<string, PricingEngineInterface>
     */
    protected Collection $engines;

    public function __construct()
    {
        $this->engines = collect();
        $this->registerDefaultEngines();
    }

    /**
     * Register a pricing engine
     */
    public function registerEngine(PricingEngineInterface $engine): void
    {
        $this->engines->put($engine->getStrategy(), $engine);
    }

    /**
     * Get a pricing engine by strategy
     */
    public function getEngine(PricingStrategy|string $strategy): ?PricingEngineInterface
    {
        $strategyKey = $strategy instanceof PricingStrategy ? $strategy->value : $strategy;

        return $this->engines->get($strategyKey);
    }

    /**
     * Calculate price using a specific strategy
     */
    public function calculate(
        PricingStrategy|string $strategy,
        string $basePrice,
        string $quantity,
        array $context = []
    ): string {
        $engine = $this->getEngine($strategy);

        if (! $engine) {
            throw new \InvalidArgumentException("Pricing engine for strategy '{$strategy}' not found");
        }

        return $engine->calculate($basePrice, $quantity, $context);
    }

    /**
     * Get price for a product at a location
     */
    public function getProductPrice(
        string $productId,
        ?string $locationId = null,
        string $quantity = '1'
    ): ?string {
        $price = \Modules\Pricing\Models\ProductPrice::query()
            ->where('product_id', $productId)
            ->forLocation($locationId)
            ->active()
            ->orderByRaw('location_id IS NOT NULL DESC') // Prefer location-specific
            ->first();

        if (! $price) {
            return null;
        }

        return $this->calculate(
            $price->strategy,
            $price->price,
            $quantity,
            $price->config ?? []
        );
    }

    /**
     * Register default pricing engines
     */
    protected function registerDefaultEngines(): void
    {
        $this->registerEngine(new FlatPricingEngine);
        $this->registerEngine(new PercentagePricingEngine);
        $this->registerEngine(new TieredPricingEngine);
        $this->registerEngine(new VolumePricingEngine);
        $this->registerEngine(new TimeBasedPricingEngine);
        $this->registerEngine(new RuleBasedPricingEngine);
    }

    /**
     * Get all registered engines
     */
    public function getEngines(): Collection
    {
        return $this->engines;
    }
}
