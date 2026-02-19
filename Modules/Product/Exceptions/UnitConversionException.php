<?php

declare(strict_types=1);

namespace Modules\Product\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

/**
 * Unit Conversion Exception
 *
 * Thrown when a unit conversion fails or is not supported.
 */
class UnitConversionException extends BusinessRuleException
{
    protected string $errorCode = 'UNIT_CONVERSION_ERROR';

    /**
     * The source unit
     */
    protected ?string $fromUnit = null;

    /**
     * The target unit
     */
    protected ?string $toUnit = null;

    /**
     * Create a new unit conversion exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $fromUnit  The source unit
     * @param  string|null  $toUnit  The target unit
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'Unit conversion failed or is not supported.',
        ?string $fromUnit = null,
        ?string $toUnit = null,
        ?string $ruleName = 'unit_conversion',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $ruleName, $code, $previous, $context);
        $this->fromUnit = $fromUnit;
        $this->toUnit = $toUnit;
    }

    /**
     * Get the source unit
     */
    public function getFromUnit(): ?string
    {
        return $this->fromUnit;
    }

    /**
     * Set the source unit
     */
    public function setFromUnit(string $fromUnit): self
    {
        $this->fromUnit = $fromUnit;

        return $this;
    }

    /**
     * Get the target unit
     */
    public function getToUnit(): ?string
    {
        return $this->toUnit;
    }

    /**
     * Set the target unit
     */
    public function setToUnit(string $toUnit): self
    {
        $this->toUnit = $toUnit;

        return $this;
    }
}
