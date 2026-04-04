<?php
namespace Modules\Inventory\Domain\ValueObjects;

class SerialStatus
{
    public const AVAILABLE = 'available';
    public const SOLD      = 'sold';
    public const RETURNED  = 'returned';
    public const SCRAPPED  = 'scrapped';
    public const IN_REPAIR = 'in_repair';

    private static array $valid = [self::AVAILABLE, self::SOLD, self::RETURNED, self::SCRAPPED, self::IN_REPAIR];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid serial status: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
