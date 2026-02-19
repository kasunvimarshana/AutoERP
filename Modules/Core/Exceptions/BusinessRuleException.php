<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

/**
 * Business Rule Exception
 *
 * Thrown when a business rule is violated.
 */
class BusinessRuleException extends DomainException
{
    protected int $httpStatusCode = 422;

    protected string $errorCode = 'BUSINESS_RULE_VIOLATION';

    /**
     * The violated rule name
     */
    protected ?string $ruleName = null;

    /**
     * Create a new business rule exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'A business rule was violated.',
        ?string $ruleName = null,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->ruleName = $ruleName;
    }

    /**
     * Get the violated rule name
     */
    public function getRuleName(): ?string
    {
        return $this->ruleName;
    }

    /**
     * Set the violated rule name
     */
    public function setRuleName(string $ruleName): self
    {
        $this->ruleName = $ruleName;

        return $this;
    }
}
