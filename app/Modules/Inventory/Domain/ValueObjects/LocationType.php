<?php
namespace Modules\Inventory\Domain\ValueObjects;

class LocationType
{
    public const INTERNAL = 'internal';
    public const EXTERNAL = 'external';
    public const VIRTUAL  = 'virtual';
    public const TRANSIT  = 'transit';

    private static array $valid = [self::INTERNAL, self::EXTERNAL, self::VIRTUAL, self::TRANSIT];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid location type: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
