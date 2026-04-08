<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;

class ProductType
{
    public const PHYSICAL  = 'physical';
    public const SERVICE   = 'service';
    public const DIGITAL   = 'digital';
    public const COMBO     = 'combo';
    public const VARIABLE  = 'variable';

    public const VALID_TYPES = [
        self::PHYSICAL,
        self::SERVICE,
        self::DIGITAL,
        self::COMBO,
        self::VARIABLE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid product type "%s". Valid types: %s', $value, implode(', ', self::VALID_TYPES))
            );
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPhysical(): bool
    {
        return $this->value === self::PHYSICAL;
    }

    public function isService(): bool
    {
        return $this->value === self::SERVICE;
    }

    public function isDigital(): bool
    {
        return $this->value === self::DIGITAL;
    }

    public function isCombo(): bool
    {
        return $this->value === self::COMBO;
    }

    public function isVariable(): bool
    {
        return $this->value === self::VARIABLE;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
