<?php

declare(strict_types=1);

namespace Modules\User\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\User\Domain\Entities\User;

class UserProfileUpdated extends BaseEvent
{
    public function __construct(public readonly User $user)
    {
        parent::__construct($user->getTenantId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->user->getId(),
            'email' => $this->user->getEmail()->value(),
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
        ]);
    }
}
