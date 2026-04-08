<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class ReturnStatus extends ValueObject
{
    public const DRAFT      = 'draft';
    public const SUBMITTED  = 'submitted';
    public const APPROVED   = 'approved';
    public const PROCESSING = 'processing';
    public const COMPLETED  = 'completed';
    public const REJECTED   = 'rejected';
    public const CANCELLED  = 'cancelled';

    private const VALID = [
        self::DRAFT,
        self::SUBMITTED,
        self::APPROVED,
        self::PROCESSING,
        self::COMPLETED,
        self::REJECTED,
        self::CANCELLED,
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid return status '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function draft(): self      { return new self(self::DRAFT); }
    public static function submitted(): self  { return new self(self::SUBMITTED); }
    public static function approved(): self   { return new self(self::APPROVED); }
    public static function processing(): self { return new self(self::PROCESSING); }
    public static function completed(): self  { return new self(self::COMPLETED); }
    public static function rejected(): self   { return new self(self::REJECTED); }
    public static function cancelled(): self  { return new self(self::CANCELLED); }

    public function getValue(): string    { return $this->value; }
    public function isDraft(): bool       { return $this->value === self::DRAFT; }
    public function isCompleted(): bool   { return $this->value === self::COMPLETED; }
    public function isRejected(): bool    { return $this->value === self::REJECTED; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
