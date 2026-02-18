<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;

class PasswordReset extends BaseEvent
{
    public function __construct(public string $email)
    {
        parent::__construct();
    }
}
