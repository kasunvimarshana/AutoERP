<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PlanType: string
{
    case Free = 'free';
    case Trial = 'trial';
    case Paid = 'paid';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Trial => 'Trial',
            self::Paid => 'Paid',
            self::Custom => 'Custom',
        };
    }

    public function requiresPayment(): bool
    {
        return in_array($this, [self::Paid, self::Custom]);
    }
}
