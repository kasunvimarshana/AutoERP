<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Transaction\Domain\Entities\Transaction;

class TransactionCreated extends BaseEvent
{
    public function __construct(public readonly Transaction $transaction)
    {
        parent::__construct($transaction->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'               => $this->transaction->getId(),
            'tenant_id'        => $this->transaction->getTenantId(),
            'reference_number' => $this->transaction->getReferenceNumber(),
        ]);
    }
}
