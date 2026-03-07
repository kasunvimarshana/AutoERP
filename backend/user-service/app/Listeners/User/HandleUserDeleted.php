<?php

namespace App\Listeners\User;

use App\Events\UserDeleted;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserDeleted implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(UserDeleted $event): void
    {
        $this->broker->publish('user.deleted', [
            'user_id' => $event->user->id,
            'tenant_id' => $event->user->tenant_id,
            'email' => $event->user->email,
        ]);
    }
}
