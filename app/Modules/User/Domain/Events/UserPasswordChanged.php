<?php
namespace Modules\User\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class UserPasswordChanged extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $userId)
    {
        parent::__construct($tenantId);
    }
}
