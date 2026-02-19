<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * FlatPricingEngine
 *
 * Simple flat-rate pricing
 */
class FlatPricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $scale = config('pricing.decimal_scale', 6);

        return bcmul($basePrice, $quantity, $scale);
    }

    public function getStrategy(): string
    {
        return 'flat';
    }

    public function validate(array $config): bool
    {
        return true;
    }
}
