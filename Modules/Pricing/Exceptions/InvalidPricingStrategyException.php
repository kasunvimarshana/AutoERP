<?php

declare(strict_types=1);

namespace Modules\Pricing\Exceptions;

use Modules\Core\Exceptions\ValidationException;

/**
 * Invalid Pricing Strategy Exception
 *
 * Thrown when a pricing strategy is invalid or not supported.
 */
class InvalidPricingStrategyException extends ValidationException
{
    protected string $errorCode = 'INVALID_PRICING_STRATEGY';

    /**
     * The invalid pricing strategy
     */
    protected ?string $strategy = null;

    /**
     * Create a new invalid pricing strategy exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $strategy  The invalid pricing strategy
     * @param  array  $errors  Validation errors
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $message = 'The pricing strategy is invalid or not supported.',
        ?string $strategy = null,
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errors, $code, $previous);
        $this->strategy = $strategy;
    }

    /**
     * Get the invalid pricing strategy
     */
    public function getStrategy(): ?string
    {
        return $this->strategy;
    }

    /**
     * Set the invalid pricing strategy
     */
    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }
}
