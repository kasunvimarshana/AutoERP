<?php

namespace App\Modules\Identity\Events;

use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Updated Event
 * 
 * Fired when a user's information is updated
 * 
 * @package App\Modules\Identity\Events
 */
class UserUpdated
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
