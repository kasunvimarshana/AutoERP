<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class OrderType extends ValueObject
{
    public const PURCHASE = 'purchase';
    public const SALE     = 'sale';

    private const VALID = [self::PURCHASE, self::SALE];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid order type '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function purchase(): self { return new self(self::PURCHASE); }
    public static function sale(): self     { return new self(self::SALE); }

    public function getValue(): string   { return $this->value; }
    public function isPurchase(): bool   { return $this->value === self::PURCHASE; }
    public function isSale(): bool       { return $this->value === self::SALE; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
