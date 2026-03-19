<?php

namespace App\Services;

use Enterprise\Core\Math\EnterpriseMath;

/**
 * UOMConversionService - Handles multi-unit conversion matrices.
 * Supports Base UOM, Buying UOM, and Selling UOM.
 */
class UOMConversionService
{
    /**
     * Convert quantity from one unit to another based on conversion factor.
     * @param float|string $quantity
     * @param float|string $factor (Multiplier to base unit)
     * @param string $direction ('TO_BASE' or 'FROM_BASE')
     */
    public function convert($quantity, $factor, string $direction = 'TO_BASE'): string
    {
        if ($direction === 'TO_BASE') {
            return EnterpriseMath::mul((string)$quantity, (string)$factor);
        } else {
            return EnterpriseMath::div((string)$quantity, (string)$factor);
        }
    }

    /**
     * Get the base unit quantity for a product.
     * e.g., 1 Box (10 Bottles, 100 Tablets) -> 100 Tablets
     */
    public function toBaseQuantity($quantity, string $fromUnit, array $conversionMatrix): string
    {
        $factor = $conversionMatrix[$fromUnit] ?? '1.0000';
        return EnterpriseMath::mul((string)$quantity, (string)$factor);
    }
}
