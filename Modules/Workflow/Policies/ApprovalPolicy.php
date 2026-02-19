<?php

declare(strict_types=1);

namespace Modules\Workflow\Policies;

use Modules\Auth\Models\User;
use Modules\Workflow\Models\Approval;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->tenant_id === $approval->tenant_id;
    }

    public function respond(User $user, Approval $approval): bool
    {
        return $user->tenant_id === $approval->tenant_id
            && in_array($user->id, [$approval->approver_id, $approval->delegated_to]);
    }

    public function delegate(User $user, Approval $approval): bool
    {
        return $user->tenant_id === $approval->tenant_id
            && $user->id === $approval->approver_id;
    }
}
