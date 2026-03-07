<?php

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UserCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function handle(UserCreated $event): void
    {
        Log::info('User created', [
            'user_id'   => $event->user->id,
            'email'     => $event->user->email,
            'tenant_id' => $event->user->tenant_id,
        ]);
    }
}
