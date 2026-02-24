<?php
namespace Modules\Sales\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class OrderShipped extends DomainEvent
{
    public function __construct(public readonly string $orderId) {}
}
