<?php
namespace Modules\Warehouse\Domain\ValueObjects;

class WarehouseType
{
    public const STANDARD    = 'standard';
    public const VIRTUAL     = 'virtual';
    public const TRANSIT     = 'transit';
    public const CONSIGNMENT = 'consignment';
    public const DROPSHIP    = 'dropship';

    private static array $valid = [self::STANDARD, self::VIRTUAL, self::TRANSIT, self::CONSIGNMENT, self::DROPSHIP];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid warehouse type: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
