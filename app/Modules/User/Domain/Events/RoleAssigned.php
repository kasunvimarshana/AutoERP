<?php

declare(strict_types=1);

namespace Modules\User\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;

class RoleAssigned extends BaseEvent
{
    public function __construct(public readonly User $user, public readonly Role $role)
    {
        parent::__construct($user->getTenantId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'userId' => $this->user->getId(),
            'roleId' => $this->role->getId(),
            'roleName' => $this->role->getName(),
        ]);
    }
}
