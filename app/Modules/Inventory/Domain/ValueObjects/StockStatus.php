<?php
namespace Modules\Inventory\Domain\ValueObjects;

class StockStatus
{
    public const AVAILABLE  = 'available';
    public const RESERVED   = 'reserved';
    public const ON_HOLD    = 'on_hold';
    public const QUARANTINE = 'quarantine';
    public const DAMAGED    = 'damaged';
    public const EXPIRED    = 'expired';

    private static array $valid = [self::AVAILABLE, self::RESERVED, self::ON_HOLD, self::QUARANTINE, self::DAMAGED, self::EXPIRED];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid stock status: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
