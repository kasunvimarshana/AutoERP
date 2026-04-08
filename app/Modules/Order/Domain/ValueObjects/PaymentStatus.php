<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class PaymentStatus extends ValueObject
{
    public const PENDING   = 'pending';
    public const PARTIAL   = 'partial';
    public const PAID      = 'paid';
    public const OVERPAID  = 'overpaid';
    public const REFUNDED  = 'refunded';

    private const VALID = [
        self::PENDING,
        self::PARTIAL,
        self::PAID,
        self::OVERPAID,
        self::REFUNDED,
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid payment status '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function pending(): self  { return new self(self::PENDING); }
    public static function partial(): self  { return new self(self::PARTIAL); }
    public static function paid(): self     { return new self(self::PAID); }
    public static function overpaid(): self { return new self(self::OVERPAID); }
    public static function refunded(): self { return new self(self::REFUNDED); }

    public function getValue(): string  { return $this->value; }
    public function isPaid(): bool      { return $this->value === self::PAID; }
    public function isPending(): bool   { return $this->value === self::PENDING; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
