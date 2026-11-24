<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services;

final class HrAuthorizationService
{
    public const VIEW_EMPLOYEES = 'hr.employees.view';

    public const CREATE_EMPLOYEES = 'hr.employees.create';

    public const UPDATE_EMPLOYEES = 'hr.employees.update';

    public const DELETE_EMPLOYEES = 'hr.employees.delete';

    public const VIEW_MASTER_DATA = 'hr.master-data.view';

    public const MANAGE_MASTER_DATA = 'hr.master-data.manage';

    public const VIEW_SHIFTS = 'hr.shifts.view';

    public const MANAGE_SHIFTS = 'hr.shifts.manage';

    public const VIEW_ATTENDANCE = 'hr.attendance.view';

    public const MANAGE_ATTENDANCE = 'hr.attendance.manage';

    public const PUNCH_ATTENDANCE = 'hr.attendance.punch';

    public const REGULARIZE_ATTENDANCE = 'hr.attendance.regularize';

    public const VIEW_LEAVES = 'hr.leaves.view';

    public const APPLY_LEAVES = 'hr.leaves.apply';

    public const MANAGE_LEAVES = 'hr.leaves.manage';

    public const VIEW_BIOMETRIC_DEVICES = 'hr.biometric.view';

    public const MANAGE_BIOMETRIC_DEVICES = 'hr.biometric.manage';

    public const SYNC_BIOMETRIC_DEVICES = 'hr.biometric.sync';

    /** @return array<string,string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW_EMPLOYEES => 'View HR employees, employee lookups, and employee relation history.',
            self::CREATE_EMPLOYEES => 'Create HR employees and employee relation graphs.',
            self::UPDATE_EMPLOYEES => 'Update HR employees, status, availability, and employee relations.',
            self::DELETE_EMPLOYEES => 'Archive unreferenced HR employees.',
            self::VIEW_MASTER_DATA => 'View HR master data and lookups.',
            self::MANAGE_MASTER_DATA => 'Create, update, and archive HR master data.',
            self::VIEW_SHIFTS => 'View HR shifts, assignments, and roster schedules.',
            self::MANAGE_SHIFTS => 'Create, modify, assign, swap, and reallocate HR shifts.',
            self::VIEW_ATTENDANCE => 'View employee attendance records, punches, and summaries.',
            self::MANAGE_ATTENDANCE => 'Directly adjust and finalize HR attendance records.',
            self::PUNCH_ATTENDANCE => 'Submit web or mobile clock-in/out and break punches.',
            self::REGULARIZE_ATTENDANCE => 'Submit or review attendance regularization requests.',
            self::VIEW_LEAVES => 'View leave balances, policies, and leave requests.',
            self::APPLY_LEAVES => 'Apply for leave and submit comp-off claims.',
            self::MANAGE_LEAVES => 'Configure leave policies, approve/reject leaves and comp-off claims.',
            self::VIEW_BIOMETRIC_DEVICES => 'View biometric attendance devices, connectivity status, and audit logs.',
            self::MANAGE_BIOMETRIC_DEVICES => 'Configure biometric devices, hardware protocols, and employee PIN mappings.',
            self::SYNC_BIOMETRIC_DEVICES => 'Trigger manual or batch log synchronization from biometric hardware.',
        ];
    }
}
