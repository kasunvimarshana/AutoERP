<?php
namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PaymentCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $paymentId)
    {
        parent::__construct($tenantId);
    }
}
