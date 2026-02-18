<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

/**
 * Status Enum
 *
 * Common status values used across the system.
 * Provides type-safe status handling with helper methods.
 *
 * @package Modules\Core\Enums
 */
enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';

    /**
     * Get all status values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all status names as an array
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Check if the status is a final state (cannot be changed)
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::ARCHIVED,
        ], true);
    }

    /**
     * Check if the status allows modifications
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING,
        ], true);
    }

    /**
     * Get human-readable label for the status
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::SUSPENDED => 'Suspended',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get color representation for UI
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::ACTIVE => 'green',
            self::INACTIVE => 'red',
            self::APPROVED => 'blue',
            self::REJECTED => 'red',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::SUSPENDED => 'orange',
            self::ARCHIVED => 'gray',
        };
    }

    /**
     * Get the next possible statuses from current status
     *
     * @return array<StatusEnum>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::APPROVED, self::REJECTED, self::CANCELLED],
            self::APPROVED => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED, self::COMPLETED],
            self::INACTIVE => [self::ACTIVE, self::ARCHIVED],
            self::SUSPENDED => [self::ACTIVE, self::CANCELLED],
            default => [],
        };
    }

    /**
     * Try to create enum from string value
     *
     * @param string $value
     * @return self|null
     */
    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
