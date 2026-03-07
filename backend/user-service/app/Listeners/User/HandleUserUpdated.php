<?php

namespace App\Listeners\User;

use App\Events\UserUpdated;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserUpdated implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(UserUpdated $event): void
    {
        $this->broker->publish('user.updated', [
            'user_id' => $event->user->id,
            'tenant_id' => $event->user->tenant_id,
            'email' => $event->user->email,
            'roles' => $event->user->roles,
            'attributes' => $event->user->attributes,
        ]);
    }
}
