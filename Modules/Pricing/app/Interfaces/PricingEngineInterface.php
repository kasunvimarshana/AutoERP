<?php

declare(strict_types=1);

namespace Modules\Pricing\Interfaces;

/**
 * Pricing Engine Interface
 *
 * Defines the contract for pricing strategy implementations
 */
interface PricingEngineInterface
{
    /**
     * Calculate price for a product
     *
     * @param  int  $productId  Product ID
     * @param  string  $quantity  Quantity
     * @param  array<string, mixed>  $context  Additional context (customer_id, location, etc.)
     * @return array<string, mixed> Price calculation result
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array;

    /**
     * Get strategy name
     */
    public function getStrategyName(): string;
}
