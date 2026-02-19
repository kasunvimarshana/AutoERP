<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * PercentagePricingEngine
 *
 * Percentage-based pricing (markup/discount)
 */
class PercentagePricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $scale = config('pricing.decimal_scale', 6);
        $percentage = $context['percentage'] ?? '0';

        // Calculate total before percentage
        $subtotal = bcmul($basePrice, $quantity, $scale);

        // Calculate percentage amount
        $percentageAmount = bcmul($subtotal, $percentage, $scale);
        $percentageAmount = bcdiv($percentageAmount, '100', $scale);

        // Add or subtract percentage
        $isMarkup = ($context['is_markup'] ?? true);

        return $isMarkup
            ? bcadd($subtotal, $percentageAmount, $scale)
            : bcsub($subtotal, $percentageAmount, $scale);
    }

    public function getStrategy(): string
    {
        return 'percentage';
    }

    public function validate(array $config): bool
    {
        return isset($config['percentage']);
    }
}
