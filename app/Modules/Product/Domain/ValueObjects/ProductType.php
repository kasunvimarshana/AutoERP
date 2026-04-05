<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

class ProductType
{
    public const PHYSICAL = 'physical';
    public const SERVICE  = 'service';
    public const DIGITAL  = 'digital';
    public const COMBO    = 'combo';
    public const VARIABLE = 'variable';

    public const VALID = [self::PHYSICAL, self::SERVICE, self::DIGITAL, self::COMBO, self::VARIABLE];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid product type: {$value}");
        }
        $this->value = $value;
    }

    public function getValue(): string { return $this->value; }
    public function isPhysical(): bool { return $this->value === self::PHYSICAL; }
    public function isService(): bool  { return $this->value === self::SERVICE; }
    public function isDigital(): bool  { return $this->value === self::DIGITAL; }
    public function isCombo(): bool    { return $this->value === self::COMBO; }
    public function isVariable(): bool { return $this->value === self::VARIABLE; }
    public function __toString(): string { return $this->value; }
}
