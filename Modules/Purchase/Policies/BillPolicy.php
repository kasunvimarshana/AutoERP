<?php

declare(strict_types=1);

namespace Modules\Purchase\Policies;

use Modules\Auth\Models\User;
use Modules\Purchase\Enums\BillStatus;
use Modules\Purchase\Models\Bill;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('bills.view');
    }

    public function view(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.view')
            && $user->tenant_id === $bill->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('bills.create');
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.update')
            && $user->tenant_id === $bill->tenant_id
            && $bill->status === BillStatus::DRAFT;
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.delete')
            && $user->tenant_id === $bill->tenant_id
            && $bill->status === BillStatus::DRAFT;
    }

    public function send(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.send')
            && $user->tenant_id === $bill->tenant_id
            && in_array($bill->status, [BillStatus::DRAFT, BillStatus::SENT]);
    }

    public function recordPayment(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.record_payment')
            && $user->tenant_id === $bill->tenant_id
            && in_array($bill->status, [BillStatus::SENT, BillStatus::PARTIALLY_PAID]);
    }

    public function cancel(User $user, Bill $bill): bool
    {
        return $user->hasPermission('bills.cancel')
            && $user->tenant_id === $bill->tenant_id
            && ! in_array($bill->status, [BillStatus::PAID, BillStatus::CANCELLED]);
    }
}
