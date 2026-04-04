<?php
namespace Modules\StockMovement\Domain\ValueObjects;
class MovementType
{
    public const RECEIPT      = 'receipt';
    public const ISSUE        = 'issue';
    public const TRANSFER_IN  = 'transfer_in';
    public const TRANSFER_OUT = 'transfer_out';
    public const ADJUSTMENT   = 'adjustment';
    public const RETURN_IN    = 'return_in';
    public const RETURN_OUT   = 'return_out';
    private static array $valid = [
        self::RECEIPT, self::ISSUE, self::TRANSFER_IN, self::TRANSFER_OUT,
        self::ADJUSTMENT, self::RETURN_IN, self::RETURN_OUT,
    ];
    private function __construct(public readonly string $value) {}
    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) throw new \InvalidArgumentException("Invalid movement type: {$v}");
        return new self($v);
    }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
