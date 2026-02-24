<?php
namespace Modules\CRM\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class ActivityCompleted extends DomainEvent
{
    public function __construct(public readonly string $activityId) {}
}
