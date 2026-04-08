<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class ReturnReason extends ValueObject
{
    public const DEFECTIVE      = 'defective';
    public const WRONG_ITEM     = 'wrong_item';
    public const DAMAGED        = 'damaged';
    public const OVERDELIVERY   = 'overdelivery';
    public const QUALITY_ISSUE  = 'quality_issue';
    public const OTHER          = 'other';

    private const VALID = [
        self::DEFECTIVE,
        self::WRONG_ITEM,
        self::DAMAGED,
        self::OVERDELIVERY,
        self::QUALITY_ISSUE,
        self::OTHER,
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid return reason '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function defective(): self     { return new self(self::DEFECTIVE); }
    public static function wrongItem(): self     { return new self(self::WRONG_ITEM); }
    public static function damaged(): self       { return new self(self::DAMAGED); }
    public static function overdelivery(): self  { return new self(self::OVERDELIVERY); }
    public static function qualityIssue(): self  { return new self(self::QUALITY_ISSUE); }
    public static function other(): self         { return new self(self::OTHER); }

    public function getValue(): string { return $this->value; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
