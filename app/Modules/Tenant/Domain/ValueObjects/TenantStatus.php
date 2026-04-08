<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class TenantStatus extends ValueObject
{
    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';
    public const TRIAL = 'trial';
    public const CANCELLED = 'cancelled';

    private const VALID = [self::ACTIVE, self::SUSPENDED, self::TRIAL, self::CANCELLED];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid tenant status '{$value}'. Must be one of: ".implode(', ', self::VALID)
            );
        }
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }

    public function isTrial(): bool
    {
        return $this->value === self::TRIAL;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
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
