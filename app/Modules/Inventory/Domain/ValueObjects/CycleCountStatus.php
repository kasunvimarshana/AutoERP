<?php
namespace Modules\Inventory\Domain\ValueObjects;

class CycleCountStatus
{
    public const DRAFT       = 'draft';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELLED   = 'cancelled';

    private static array $valid = [self::DRAFT, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid cycle count status: {$v}");
        }
        return new self($v);
    }

    public static function assertValid(string $v): void { self::from($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
