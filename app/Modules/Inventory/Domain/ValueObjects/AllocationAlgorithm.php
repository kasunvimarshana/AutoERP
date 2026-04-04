<?php
namespace Modules\Inventory\Domain\ValueObjects;

class AllocationAlgorithm
{
    public const FIFO       = 'fifo';
    public const LIFO       = 'lifo';
    public const FEFO       = 'fefo';
    public const NEAREST    = 'nearest';
    public const ZONE_BASED = 'zone_based';

    private static array $valid = [self::FIFO, self::LIFO, self::FEFO, self::NEAREST, self::ZONE_BASED];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid allocation algorithm: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
