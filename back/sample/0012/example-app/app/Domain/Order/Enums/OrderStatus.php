<?php

namespace App\Domain\Order\Enums;

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Shipped   = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending Payment',
            self::Paid      => 'Paid',
            self::Shipped   => 'Shipped',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Shipped, self::Cancelled]);
    }
}
