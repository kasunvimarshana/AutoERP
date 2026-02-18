<?php

declare(strict_types=1);

namespace Modules\HR\Enums;

/**
 * Employee Status Enum
 *
 * Employment status lifecycle.
 *
 * @package Modules\HR\Enums
 */
enum EmployeeStatusEnum: string
{
    case ACTIVE = 'active';
    case PROBATION = 'probation';
    case ON_LEAVE = 'on_leave';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
    case RESIGNED = 'resigned';
    case RETIRED = 'retired';

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
            self::ACTIVE => 'Active',
            self::PROBATION => 'On Probation',
            self::ON_LEAVE => 'On Leave',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated',
            self::RESIGNED => 'Resigned',
            self::RETIRED => 'Retired',
        };
    }

    /**
     * Check if employee is currently employed
     *
     * @return bool
     */
    public function isEmployed(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::PROBATION,
            self::ON_LEAVE,
            self::SUSPENDED,
        ], true);
    }

    /**
     * Check if employee can receive payroll
     *
     * @return bool
     */
    public function canReceivePayroll(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::PROBATION,
            self::ON_LEAVE,
        ], true);
    }

    /**
     * Check if status is final (no return)
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::TERMINATED,
            self::RESIGNED,
            self::RETIRED,
        ], true);
    }

    /**
     * Get color for UI
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::PROBATION => 'yellow',
            self::ON_LEAVE => 'blue',
            self::SUSPENDED => 'orange',
            self::TERMINATED => 'red',
            self::RESIGNED => 'gray',
            self::RETIRED => 'gray',
        };
    }
}
