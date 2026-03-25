<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

use Modules\Core\Domain\Events\UserScopedEvent;

class UserRegistered extends UserScopedEvent
{
    public readonly string $email;

    public readonly string $firstName;

    public readonly string $lastName;

    public function __construct(int $userId, string $email, string $firstName, string $lastName)
    {
        parent::__construct($userId);
        $this->email     = $email;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'email'     => $this->email,
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
        ]);
    }
}
