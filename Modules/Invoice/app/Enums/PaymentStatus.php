<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

/**
 * Payment Status Enum
 *
 * Defines the possible statuses for a payment
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';

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
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::VOIDED => 'Voided',
            self::REFUNDED => 'Refunded',
        };
    }
}
