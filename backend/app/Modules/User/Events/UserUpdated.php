<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user
    ) {}
}
