<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if the payment is finalized.
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::COMPLETED, self::REFUNDED]);
    }

    /**
     * Check if the payment can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this === self::PENDING;
    }
}
