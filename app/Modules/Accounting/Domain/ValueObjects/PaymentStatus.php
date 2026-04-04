<?php
namespace Modules\Accounting\Domain\ValueObjects;

class PaymentStatus
{
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';
    public const CANCELLED = 'cancelled';

    private static array $valid = [self::PENDING, self::COMPLETED, self::FAILED, self::REFUNDED, self::CANCELLED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid PaymentStatus: {$value}");
        }
    }

    public static function from(string $v): self { return new self($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
