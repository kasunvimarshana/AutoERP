<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * TieredPricingEngine
 *
 * Tiered pricing based on quantity breakpoints
 */
class TieredPricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $scale = config('pricing.decimal_scale', 6);
        $tiers = $context['tiers'] ?? [];

        if (empty($tiers)) {
            return bcmul($basePrice, $quantity, $scale);
        }

        // Sort tiers by min_quantity
        usort($tiers, fn ($a, $b) => bccomp($a['min_quantity'], $b['min_quantity']));

        // Find applicable tier
        $applicableTier = null;
        foreach ($tiers as $tier) {
            if (bccomp($quantity, $tier['min_quantity']) >= 0) {
                $applicableTier = $tier;
            } else {
                break;
            }
        }

        if (! $applicableTier) {
            return bcmul($basePrice, $quantity, $scale);
        }

        // Use tier price
        $tierPrice = $applicableTier['price'] ?? $basePrice;

        return bcmul($tierPrice, $quantity, $scale);
    }

    public function getStrategy(): string
    {
        return 'tiered';
    }

    public function validate(array $config): bool
    {
        if (! isset($config['tiers']) || ! is_array($config['tiers'])) {
            return false;
        }

        foreach ($config['tiers'] as $tier) {
            if (! isset($tier['min_quantity']) || ! isset($tier['price'])) {
                return false;
            }
        }

        return true;
    }
}
