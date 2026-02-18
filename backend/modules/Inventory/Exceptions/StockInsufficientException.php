<?php

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Thrown when attempting to allocate or reserve more stock than available
 */
class StockInsufficientException extends DomainException
{
    protected int $statusCode = 422;

    public static function forProduct(
        int $productId,
        float $requested,
        float $available,
        ?int $warehouseId = null
    ): self {
        $location = $warehouseId ? " at warehouse {$warehouseId}" : '';
        
        return new self(
            "Insufficient stock for product {$productId}{$location}. Requested: {$requested}, Available: {$available}",
            [
                'product_id' => $productId,
                'requested' => $requested,
                'available' => $available,
                'warehouse_id' => $warehouseId,
            ]
        );
    }

    public static function forVariant(
        int $variantId,
        float $requested,
        float $available
    ): self {
        return new self(
            "Insufficient stock for variant {$variantId}. Requested: {$requested}, Available: {$available}",
            [
                'variant_id' => $variantId,
                'requested' => $requested,
                'available' => $available,
            ]
        );
    }
}
