<?php
namespace Modules\Warehouse\Domain\ValueObjects;

class LocationType
{
    public const SHELF   = 'shelf';
    public const BIN     = 'bin';
    public const RACK    = 'rack';
    public const FLOOR   = 'floor';
    public const VIRTUAL = 'virtual';

    private static array $valid = [self::SHELF, self::BIN, self::RACK, self::FLOOR, self::VIRTUAL];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid location type: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
