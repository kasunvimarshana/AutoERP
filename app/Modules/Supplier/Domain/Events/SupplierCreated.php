<?php
namespace Modules\Supplier\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SupplierCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
