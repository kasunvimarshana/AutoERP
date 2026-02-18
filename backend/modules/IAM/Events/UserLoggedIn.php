<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;
use Modules\IAM\Models\User;

class UserLoggedIn extends BaseEvent
{
    public function __construct(
        public User $user,
        public string $ipAddress,
        public string $userAgent
    ) {
        parent::__construct();
    }
}
