<?php

declare(strict_types=1);

namespace Modules\HR\Enums;

/**
 * Leave Status Enum
 *
 * Leave request status workflow.
 *
 * @package Modules\HR\Enums
 */
enum LeaveStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case TAKEN = 'taken';

    /**
     * Get all status values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::TAKEN => 'Taken',
        };
    }

    /**
     * Check if leave can be cancelled
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::APPROVED,
        ], true);
    }

    /**
     * Check if leave affects balance
     *
     * @return bool
     */
    public function affectsBalance(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::TAKEN,
        ], true);
    }

    /**
     * Check if status is final
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::CANCELLED,
            self::TAKEN,
        ], true);
    }

    /**
     * Get next possible statuses
     *
     * @return array<LeaveStatusEnum>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED, self::CANCELLED],
            self::APPROVED => [self::TAKEN, self::CANCELLED],
            default => [],
        };
    }

    /**
     * Get color for UI
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
            self::TAKEN => 'blue',
        };
    }
}
