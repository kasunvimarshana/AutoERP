<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class ProductType extends ValueObject
{
    public const PHYSICAL = 'physical';
    public const SERVICE = 'service';
    public const DIGITAL = 'digital';
    public const COMBO = 'combo';
    public const VARIABLE = 'variable';

    private const VALID = [self::PHYSICAL, self::SERVICE, self::DIGITAL, self::COMBO, self::VARIABLE];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid product type '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public function isStockable(): bool
    {
        return in_array($this->value, [self::PHYSICAL, self::COMBO], true);
    }

    public function requiresVariants(): bool
    {
        return $this->value === self::VARIABLE;
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

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
