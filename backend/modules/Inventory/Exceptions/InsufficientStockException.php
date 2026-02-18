<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Insufficient Stock Exception
 * 
 * Thrown when attempting to allocate or reserve stock that exceeds available quantity
 */
class InsufficientStockException extends DomainException
{
    protected int $statusCode = 422;

    /**
     * Create exception instance
     */
    public static function forProduct(string $productName, float $requested, float $available): self
    {
        $exception = new self(
            "Insufficient stock for product '{$productName}'. Requested: {$requested}, Available: {$available}"
        );
        
        $exception->context = [
            'product' => $productName,
            'requested_quantity' => $requested,
            'available_quantity' => $available,
            'error_code' => 'INSUFFICIENT_STOCK',
        ];
        
        return $exception;
    }

    /**
     * Create exception for warehouse-specific stock shortage
     */
    public static function forWarehouse(string $productName, string $warehouseName, float $shortfall): self
    {
        $exception = new self(
            "Cannot fulfill order from warehouse '{$warehouseName}'. Product '{$productName}' short by {$shortfall} units"
        );
        
        $exception->context = [
            'product' => $productName,
            'warehouse' => $warehouseName,
            'shortfall' => $shortfall,
            'error_code' => 'WAREHOUSE_STOCK_SHORTAGE',
        ];
        
        return $exception;
    }
}
