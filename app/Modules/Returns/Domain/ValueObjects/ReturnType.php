<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class ReturnType extends ValueObject
{
    public const PURCHASE_RETURN = 'purchase_return';
    public const SALE_RETURN     = 'sale_return';

    private const VALID = [self::PURCHASE_RETURN, self::SALE_RETURN];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid return type '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function purchaseReturn(): self { return new self(self::PURCHASE_RETURN); }
    public static function saleReturn(): self     { return new self(self::SALE_RETURN); }

    public function getValue(): string        { return $this->value; }
    public function isPurchaseReturn(): bool  { return $this->value === self::PURCHASE_RETURN; }
    public function isSaleReturn(): bool      { return $this->value === self::SALE_RETURN; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
