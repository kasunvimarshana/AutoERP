<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * StockCountStatus Enum
 *
 * Defines status values for physical stock counts.
 */
enum StockCountStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case RECONCILED = 'reconciled';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::RECONCILED => 'Reconciled',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get available transitions from current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PLANNED => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [self::RECONCILED],
            self::RECONCILED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if transition to given status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
