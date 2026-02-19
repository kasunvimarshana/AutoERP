<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Modules\Accounting\Models\FiscalPeriod;
use Modules\Auth\Models\User;

class FiscalPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fiscal_periods.view');
    }

    public function view(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.view')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fiscal_periods.create');
    }

    public function update(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.update')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function delete(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.delete')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function close(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.close')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function reopen(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.reopen')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function lock(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.lock')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function restore(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.delete')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }

    public function forceDelete(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasPermission('fiscal_periods.force_delete')
            && $user->tenant_id === $fiscalPeriod->tenant_id;
    }
}
