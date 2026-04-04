<?php
namespace Modules\Warehouse\Domain\ValueObjects;

class ZoneType
{
    public const STORAGE    = 'storage';
    public const RECEIVING  = 'receiving';
    public const SHIPPING   = 'shipping';
    public const STAGING    = 'staging';
    public const QUARANTINE = 'quarantine';
    public const RETURNS    = 'returns';

    private static array $valid = [self::STORAGE, self::RECEIVING, self::SHIPPING, self::STAGING, self::QUARANTINE, self::RETURNS];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid zone type: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
