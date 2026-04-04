<?php
namespace Modules\Inventory\Domain\ValueObjects;

class CycleCountMethod
{
    public const FULL     = 'full';
    public const PARTIAL  = 'partial';
    public const ABC      = 'abc';
    public const RANDOM   = 'random';
    public const PERIODIC = 'periodic';

    private static array $valid = [self::FULL, self::PARTIAL, self::ABC, self::RANDOM, self::PERIODIC];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid cycle count method: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
