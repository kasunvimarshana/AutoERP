<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

/**
 * Commission Status Enum
 *
 * Defines the possible statuses for driver commissions
 */
enum CommissionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    /**
     * Get all status values
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }
}
