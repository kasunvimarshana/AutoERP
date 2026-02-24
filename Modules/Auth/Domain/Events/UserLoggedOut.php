<?php
namespace Modules\Auth\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class UserLoggedOut extends DomainEvent
{
    public function __construct(public readonly string $userId)
    {
        parent::__construct();
    }
}
