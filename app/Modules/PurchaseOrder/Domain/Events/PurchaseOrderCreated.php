<?php
namespace Modules\PurchaseOrder\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class PurchaseOrderCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $poId)
    {
        parent::__construct($tenantId);
    }
}
