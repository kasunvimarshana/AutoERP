<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class ProductStatus extends ValueObject
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const DRAFT = 'draft';
    public const DISCONTINUED = 'discontinued';

    private const VALID = [self::ACTIVE, self::INACTIVE, self::DRAFT, self::DISCONTINUED];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid product status '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->value === self::INACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isDiscontinued(): bool
    {
        return $this->value === self::DISCONTINUED;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
