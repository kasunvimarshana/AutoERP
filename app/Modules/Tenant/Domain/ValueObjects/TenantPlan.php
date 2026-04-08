<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class TenantPlan extends ValueObject
{
    public const FREE = 'free';
    public const STARTER = 'starter';
    public const PROFESSIONAL = 'professional';
    public const ENTERPRISE = 'enterprise';

    private const VALID = [self::FREE, self::STARTER, self::PROFESSIONAL, self::ENTERPRISE];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid tenant plan '{$value}'. Must be one of: ".implode(', ', self::VALID)
            );
        }
    }

    public function isFree(): bool
    {
        return $this->value === self::FREE;
    }

    public function isEnterprise(): bool
    {
        return $this->value === self::ENTERPRISE;
    }

    public function isPaid(): bool
    {
        return $this->value !== self::FREE;
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
