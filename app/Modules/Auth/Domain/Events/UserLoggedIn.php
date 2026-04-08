<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

use Modules\Core\Domain\Events\UserScopedEvent;

class UserLoggedIn extends UserScopedEvent
{
    public readonly string $email;

    public readonly string $ipAddress;

    public readonly string $userAgent;

    public function __construct(
        int $userId,
        string $email,
        string $ipAddress = '',
        string $userAgent = '',
    ) {
        parent::__construct($userId);
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'email'     => $this->email,
            'ipAddress' => $this->ipAddress,
            // userAgent is intentionally excluded from the broadcast payload:
            // it is captured for server-side audit/logging purposes only and
            // should not be surfaced to WebSocket clients.
        ]);
    }
}
