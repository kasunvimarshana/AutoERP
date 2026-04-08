<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class OrderStatus extends ValueObject
{
    public const DRAFT       = 'draft';
    public const CONFIRMED   = 'confirmed';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELLED   = 'cancelled';
    public const ON_HOLD     = 'on_hold';

    private const VALID = [
        self::DRAFT,
        self::CONFIRMED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::CANCELLED,
        self::ON_HOLD,
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid order status '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function draft(): self       { return new self(self::DRAFT); }
    public static function confirmed(): self   { return new self(self::CONFIRMED); }
    public static function inProgress(): self  { return new self(self::IN_PROGRESS); }
    public static function completed(): self   { return new self(self::COMPLETED); }
    public static function cancelled(): self   { return new self(self::CANCELLED); }
    public static function onHold(): self      { return new self(self::ON_HOLD); }

    public function getValue(): string { return $this->value; }

    public function isDraft(): bool      { return $this->value === self::DRAFT; }
    public function isCompleted(): bool  { return $this->value === self::COMPLETED; }
    public function isCancelled(): bool  { return $this->value === self::CANCELLED; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
