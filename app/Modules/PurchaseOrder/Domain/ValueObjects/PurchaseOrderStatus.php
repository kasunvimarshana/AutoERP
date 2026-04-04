<?php
namespace Modules\PurchaseOrder\Domain\ValueObjects;
class PurchaseOrderStatus
{
    public const DRAFT     = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED  = 'approved';
    public const PARTIAL   = 'partial';
    public const RECEIVED  = 'received';
    public const CANCELLED = 'cancelled';
    public const CLOSED    = 'closed';
    private static array $valid = [self::DRAFT, self::SUBMITTED, self::APPROVED, self::PARTIAL, self::RECEIVED, self::CANCELLED, self::CLOSED];
    private function __construct(public readonly string $value) {}
    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) throw new \InvalidArgumentException("Invalid PO status: {$v}");
        return new self($v);
    }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
