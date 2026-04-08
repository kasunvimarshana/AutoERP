<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class CycleCountStatus
{
    public const DRAFT       = 'draft';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELLED   = 'cancelled';

    public const VALID_STATUSES = [
        self::DRAFT,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::CANCELLED,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid cycle count status: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isInProgress(): bool
    {
        return $this->value === self::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function values(): array
    {
        return self::VALID_STATUSES;
    }
}
