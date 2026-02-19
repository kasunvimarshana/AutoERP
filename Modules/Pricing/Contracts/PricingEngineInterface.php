<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

/**
 * PricingEngineInterface
 *
 * Contract for pricing calculation engines
 */
interface PricingEngineInterface
{
    /**
     * Calculate price based on quantity and context
     *
     * @param  array  $context  Additional context (location, date, customer, etc.)
     * @return string Calculated price
     */
    public function calculate(string $basePrice, string $quantity, array $context = []): string;

    /**
     * Get the strategy name
     */
    public function getStrategy(): string;

    /**
     * Validate engine configuration
     */
    public function validate(array $config): bool;
}
