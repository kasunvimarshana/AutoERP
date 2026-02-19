<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::PastDue => 'Past Due',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::PastDue]);
    }

    public function canBeActivated(): bool
    {
        return in_array($this, [self::Trial, self::Suspended, self::Expired]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::PastDue, self::Suspended]);
    }
}
