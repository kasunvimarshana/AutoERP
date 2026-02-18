<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;

class UserDeleted extends BaseEvent
{
    public function __construct(public int $userId, public string $email)
    {
        parent::__construct();
    }
}
