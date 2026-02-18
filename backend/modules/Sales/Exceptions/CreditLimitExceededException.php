<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Credit Limit Exceeded Exception
 * 
 * Thrown when customer's order would exceed their available credit limit
 */
class CreditLimitExceededException extends DomainException
{
    protected int $statusCode = 422;

    /**
     * Create exception for credit limit violation
     */
    public static function forCustomer(
        string $customerName,
        float $creditLimit,
        float $currentBalance,
        float $orderAmount
    ): self {
        $availableCredit = $creditLimit - $currentBalance;
        $excess = $orderAmount - $availableCredit;
        
        $exception = new self(
            "Order exceeds available credit for customer '{$customerName}'. " .
            "Order amount: {$orderAmount}, Available credit: {$availableCredit}"
        );
        
        $exception->context = [
            'customer' => $customerName,
            'credit_limit' => $creditLimit,
            'current_balance' => $currentBalance,
            'available_credit' => $availableCredit,
            'order_amount' => $orderAmount,
            'excess_amount' => $excess,
            'error_code' => 'CREDIT_LIMIT_EXCEEDED',
        ];
        
        return $exception;
    }
}
