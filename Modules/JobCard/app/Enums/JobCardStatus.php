<?php

declare(strict_types=1);

namespace Modules\JobCard\Enums;

/**
 * Job Card Status Enum
 *
 * Defines the possible statuses for a job card
 */
enum JobCardStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case WAITING_PARTS = 'waiting_parts';
    case QUALITY_CHECK = 'quality_check';
    case COMPLETED = 'completed';
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
            self::IN_PROGRESS => 'In Progress',
            self::ON_HOLD => 'On Hold',
            self::WAITING_PARTS => 'Waiting for Parts',
            self::QUALITY_CHECK => 'Quality Check',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
