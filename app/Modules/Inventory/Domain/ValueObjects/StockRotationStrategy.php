<?php
namespace Modules\Inventory\Domain\ValueObjects;

class StockRotationStrategy
{
    public const FIFO = 'fifo';
    public const LIFO = 'lifo';
    public const FEFO = 'fefo';
    public const LEFO = 'lefo';

    private static array $valid = [self::FIFO, self::LIFO, self::FEFO, self::LEFO];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid stock rotation strategy: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
