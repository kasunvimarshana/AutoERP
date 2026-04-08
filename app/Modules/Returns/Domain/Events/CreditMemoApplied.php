<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Returns\Domain\Entities\CreditMemo;

class CreditMemoApplied extends BaseEvent
{
    public function __construct(public readonly CreditMemo $creditMemo)
    {
        parent::__construct($creditMemo->getTenantId(), $creditMemo->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'               => $this->creditMemo->getId(),
            'tenant_id'        => $this->creditMemo->getTenantId(),
            'reference_number' => $this->creditMemo->getReferenceNumber(),
            'status'           => $this->creditMemo->getStatus(),
        ]);
    }
}
