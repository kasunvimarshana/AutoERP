<?php
namespace Modules\Purchase\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class PurchaseOrderApproved extends DomainEvent
{
    public function __construct(public readonly string $purchaseOrderId) {}
}
