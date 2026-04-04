<?php
namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class RefundProcessed extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $refundId)
    {
        parent::__construct($tenantId);
    }
}
