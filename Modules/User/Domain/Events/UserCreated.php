<?php
namespace Modules\User\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class UserCreated extends DomainEvent
{
    public function __construct(public readonly string $userId)
    {
        parent::__construct();
    }
}
