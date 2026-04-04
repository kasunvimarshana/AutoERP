<?php

namespace Modules\Dispatch\Domain\ValueObjects;

class DispatchStatus
{
    public const PENDING    = 'pending';
    public const PROCESSING = 'processing';
    public const DISPATCHED = 'dispatched';
    public const DELIVERED  = 'delivered';
    public const FAILED     = 'failed';
    public const CANCELLED  = 'cancelled';

    private static array $valid = [
        self::PENDING,
        self::PROCESSING,
        self::DISPATCHED,
        self::DELIVERED,
        self::FAILED,
        self::CANCELLED,
    ];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid dispatch status: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array
    {
        return self::$valid;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
