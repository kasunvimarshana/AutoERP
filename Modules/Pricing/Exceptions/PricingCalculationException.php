<?php

declare(strict_types=1);

namespace Modules\Pricing\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

/**
 * Pricing Calculation Exception
 *
 * Thrown when a pricing calculation fails.
 */
class PricingCalculationException extends BusinessRuleException
{
    protected string $errorCode = 'PRICING_CALCULATION_ERROR';

    /**
     * Create a new pricing calculation exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'Pricing calculation failed.',
        ?string $ruleName = 'pricing_calculation',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $ruleName, $code, $previous, $context);
    }
}
