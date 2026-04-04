<?php
namespace Modules\UoM\Domain\ValueObjects;

final class UomType
{
    public const BASE      = 'base';
    public const PURCHASE  = 'purchase';
    public const SALES     = 'sales';
    public const INVENTORY = 'inventory';

    private static array $valid = [self::BASE, self::PURCHASE, self::SALES, self::INVENTORY];

    public function __construct(private readonly string $value)
    {
        if (!self::valid($this->value)) {
            throw new \InvalidArgumentException("Invalid UomType: {$this->value}");
        }
    }

    public static function from(string $value): self { return new self($value); }
    public static function valid(string $value): bool { return in_array($value, self::$valid, true); }
    public function __toString(): string { return $this->value; }
}
