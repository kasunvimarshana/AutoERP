<?php

namespace App\Listeners\User;

use App\Events\UserCreated;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserCreated implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(UserCreated $event): void
    {
        $this->broker->publish('user.created', [
            'user_id' => $event->user->id,
            'tenant_id' => $event->user->tenant_id,
            'email' => $event->user->email,
            'name' => $event->user->name,
            'roles' => $event->user->roles,
            'attributes' => $event->user->attributes,
        ]);
    }
}
