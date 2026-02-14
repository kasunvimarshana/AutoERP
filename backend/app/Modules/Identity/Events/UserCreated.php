<?php

namespace App\Modules\Identity\Events;

use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Created Event
 * 
 * Fired when a new user is created in the system
 * 
 * @package App\Modules\Identity\Events
 */
class UserCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param User $user
     */
    public function __construct(public User $user)
    {
    }
}
