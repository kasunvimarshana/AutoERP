<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Invalid Order State Exception
 * 
 * Thrown when attempting state transitions that are not allowed for current order status
 */
class InvalidOrderStateException extends DomainException
{
    protected int $statusCode = 422;

    /**
     * Create exception for invalid state transition
     */
    public static function cannotTransition(string $orderNumber, string $fromState, string $toState): self
    {
        $exception = new self(
            "Cannot transition order {$orderNumber} from '{$fromState}' to '{$toState}'"
        );
        
        $exception->context = [
            'order_number' => $orderNumber,
            'current_state' => $fromState,
            'attempted_state' => $toState,
            'error_code' => 'INVALID_STATE_TRANSITION',
        ];
        
        return $exception;
    }

    /**
     * Create exception for operations not allowed in current state
     */
    public static function operationNotAllowed(string $orderNumber, string $currentState, string $operation): self
    {
        $exception = new self(
            "Cannot perform '{$operation}' on order {$orderNumber} in '{$currentState}' state"
        );
        
        $exception->context = [
            'order_number' => $orderNumber,
            'current_state' => $currentState,
            'attempted_operation' => $operation,
            'error_code' => 'OPERATION_NOT_ALLOWED',
        ];
        
        return $exception;
    }
}
