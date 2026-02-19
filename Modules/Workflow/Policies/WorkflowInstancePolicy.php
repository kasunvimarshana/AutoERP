<?php

declare(strict_types=1);

namespace Modules\Workflow\Policies;

use Modules\Auth\Models\User;
use Modules\Workflow\Models\WorkflowInstance;

class WorkflowInstancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkflowInstance $instance): bool
    {
        return $user->tenant_id === $instance->tenant_id;
    }

    public function cancel(User $user, WorkflowInstance $instance): bool
    {
        return $user->tenant_id === $instance->tenant_id && $instance->isActive();
    }

    public function resume(User $user, WorkflowInstance $instance): bool
    {
        return $user->tenant_id === $instance->tenant_id && $instance->isActive();
    }
}
