<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

final class HrAuthorizationService
{
    public const VIEW_EMPLOYEES = 'hr.employees.view';

    public const CREATE_EMPLOYEES = 'hr.employees.create';

    public const UPDATE_EMPLOYEES = 'hr.employees.update';

    public const DELETE_EMPLOYEES = 'hr.employees.delete';

    public const VIEW_MASTER_DATA = 'hr.master-data.view';

    public const MANAGE_MASTER_DATA = 'hr.master-data.manage';

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
        ];
    }
}
