<?php
namespace Modules\Accounting\Domain\ValueObjects;

class JournalEntryStatus
{
    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const REVERSED = 'reversed';

    private static array $valid = [self::DRAFT, self::POSTED, self::REVERSED];

    public function __construct(private readonly string $value)
    {
        if (!in_array($value, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid JournalEntryStatus: {$value}");
        }
    }

    public static function from(string $v): self { return new self($v); }
    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
