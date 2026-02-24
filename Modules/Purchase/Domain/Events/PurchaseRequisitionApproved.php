<?php

namespace Modules\Purchase\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PurchaseRequisitionApproved extends DomainEvent
{
    public function __construct(
        public readonly string  $requisitionId,
        public readonly ?string $approvedBy,
    ) {}
}
