<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PaymentCompleted extends BaseEvent
{
    public int $paymentId;

    public function __construct(int $tenantId, int $paymentId)
    {
        parent::__construct($tenantId);
        $this->paymentId = $paymentId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['paymentId' => $this->paymentId]);
    }
}
