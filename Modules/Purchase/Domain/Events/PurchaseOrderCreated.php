<?php
namespace Modules\Purchase\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class PurchaseOrderCreated extends DomainEvent
{
    public function __construct(public readonly string $purchaseOrderId) {}
}
