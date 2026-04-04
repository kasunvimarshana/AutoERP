<?php
namespace Modules\GoodsReceipt\Domain\ValueObjects;
class GoodsReceiptStatus
{
    public const PENDING          = 'pending';
    public const UNDER_INSPECTION = 'under_inspection';
    public const INSPECTED        = 'inspected';
    public const PUT_AWAY         = 'put_away';
    public const COMPLETED        = 'completed';
    public const CANCELLED        = 'cancelled';
    private static array $valid = [
        self::PENDING, self::UNDER_INSPECTION, self::INSPECTED,
        self::PUT_AWAY, self::COMPLETED, self::CANCELLED,
    ];
    private function __construct(public readonly string $value) {}
    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) throw new \InvalidArgumentException("Invalid GR status: {$v}");
        return new self($v);
    }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
