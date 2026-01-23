<?php

namespace App\Modules\AuthManagement\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MfaEnabled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $mfaType
    ) {}
}
