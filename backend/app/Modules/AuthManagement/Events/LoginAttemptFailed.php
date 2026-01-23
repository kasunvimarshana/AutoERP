<?php

namespace App\Modules\AuthManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginAttemptFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $email,
        public string $ipAddress,
        public string $reason
    ) {}
}
