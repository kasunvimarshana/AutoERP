<?php

namespace Modules\SalesOrder\Domain\ValueObjects;

class SalesOrderStatus
{
    public const DRAFT      = 'draft';
    public const CONFIRMED  = 'confirmed';
    public const PICKING    = 'picking';
    public const PACKING    = 'packing';
    public const SHIPPED    = 'shipped';
    public const DELIVERED  = 'delivered';
    public const CANCELLED  = 'cancelled';
    public const CLOSED     = 'closed';

    private static array $valid = [
        self::DRAFT,
        self::CONFIRMED,
        self::PICKING,
        self::PACKING,
        self::SHIPPED,
        self::DELIVERED,
        self::CANCELLED,
        self::CLOSED,
    ];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid SO status: {$v}");
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
