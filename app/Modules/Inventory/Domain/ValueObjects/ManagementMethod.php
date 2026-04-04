<?php
namespace Modules\Inventory\Domain\ValueObjects;

class ManagementMethod
{
    public const STANDARD         = 'standard';
    public const BATCH            = 'batch';
    public const LOT              = 'lot';
    public const SERIAL           = 'serial';
    public const BATCH_AND_SERIAL = 'batch_and_serial';

    private static array $valid = [self::STANDARD, self::BATCH, self::LOT, self::SERIAL, self::BATCH_AND_SERIAL];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid management method: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
