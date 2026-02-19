<?php

declare(strict_types=1);

namespace Modules\Workflow\Policies;

use Modules\Auth\Models\User;
use Modules\Workflow\Models\Workflow;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id && $workflow->canEdit();
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function execute(User $user, Workflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id && $workflow->canExecute();
    }
}
