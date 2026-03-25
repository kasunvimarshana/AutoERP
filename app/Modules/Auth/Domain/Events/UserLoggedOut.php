<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

use Modules\Core\Domain\Events\UserScopedEvent;

class UserLoggedOut extends UserScopedEvent
{
    public readonly string $email;

    public function __construct(int $userId, string $email)
    {
        parent::__construct($userId);
        $this->email = $email;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'email' => $this->email,
        ]);
    }
}
