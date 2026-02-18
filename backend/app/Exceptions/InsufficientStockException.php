<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Insufficient Stock Exception
 * 
 * Thrown when there is not enough stock for an operation
 */
class InsufficientStockException extends BusinessException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'INSUFFICIENT_STOCK';

    public function __construct(string $productSku, float $requested, float $available)
    {
        parent::__construct(
            "Insufficient stock for product {$productSku}",
            [
                'product_sku' => $productSku,
                'requested' => $requested,
                'available' => $available,
                'shortage' => $requested - $available,
            ]
        );
    }
}
