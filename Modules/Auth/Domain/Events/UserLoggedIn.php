<?php
namespace Modules\Auth\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class UserLoggedIn extends DomainEvent
{
    public function __construct(public readonly string $userEmail, public readonly string $ipAddress)
    {
        parent::__construct();
    }
}
