<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Sales\Models\SalesOrder;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-sales-orders');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->tenant_id !== $salesOrder->tenant_id) {
            return false;
        }

        if (!$user->can('view-sales-orders')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $salesOrder->organization_id) {
            if ($user->organization_id !== $salesOrder->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $salesOrder->branch_id) {
            return $user->branch_id === $salesOrder->branch_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-sales-orders');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->tenant_id !== $salesOrder->tenant_id) {
            return false;
        }

        if (!$user->can('edit-sales-orders')) {
            return false;
        }

        if (!$salesOrder->isEditable()) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $salesOrder->organization_id) {
            if ($user->organization_id !== $salesOrder->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $salesOrder->branch_id) {
            return $user->branch_id === $salesOrder->branch_id;
        }

        return true;
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->tenant_id !== $salesOrder->tenant_id) {
            return false;
        }

        if (!$user->can('delete-sales-orders')) {
            return false;
        }

        if ($salesOrder->isCompleted()) {
            return false;
        }

        return $user->hasRole('super-admin') || $user->hasRole('admin');
    }

    public function approve(User $user, SalesOrder $salesOrder): bool
    {
        if ($user->tenant_id !== $salesOrder->tenant_id) {
            return false;
        }

        return $user->can('approve-sales-orders');
    }
}
