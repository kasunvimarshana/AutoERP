<?php

namespace Modules\Returns\Domain\ValueObjects;

class RmaStatus
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const EXPIRED = 'expired';
    const CANCELLED = 'cancelled';

    private static array $valid = [
        self::PENDING,
        self::APPROVED,
        self::EXPIRED,
        self::CANCELLED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value): self
    {
        if (!self::valid($value)) {
            throw new \InvalidArgumentException("Invalid RMA status: {$value}");
        }

        return new self($value);
    }

    public static function valid(string $value): bool
    {
        return in_array($value, self::$valid, true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
