<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class EntryStatus extends ValueObject
{
    public const DRAFT  = 'draft';
    public const POSTED = 'posted';
    public const VOIDED = 'voided';

    public const ALL = [self::DRAFT, self::POSTED, self::VOIDED];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new InvalidArgumentException(
                "Invalid entry status '{$value}'. Allowed: " . implode(', ', self::ALL)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->value === self::POSTED;
    }

    public function isVoided(): bool
    {
        return $this->value === self::VOIDED;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }
}
