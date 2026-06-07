<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Modules\Hr\Models\HrEmployee;

final class EmployeeNumberService
{
    public function next(int $tenantId): string
    {
        $next = ((int) HrEmployee::query()->withTrashed()->where('tenant_id', $tenantId)->max('id')) + 1;

        do {
            $number = 'EMP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (HrEmployee::query()->withTrashed()->where('tenant_id', $tenantId)->where('employee_number', $number)->exists());

        return $number;
    }
}
