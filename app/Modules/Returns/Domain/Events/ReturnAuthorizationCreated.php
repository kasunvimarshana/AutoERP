<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Returns\Domain\Entities\ReturnAuthorization;

class ReturnAuthorizationCreated extends BaseEvent
{
    public function __construct(public readonly ReturnAuthorization $authorization)
    {
        parent::__construct($authorization->getTenantId(), $authorization->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'         => $this->authorization->getId(),
            'tenant_id'  => $this->authorization->getTenantId(),
            'rma_number' => $this->authorization->getRmaNumber(),
            'status'     => $this->authorization->getStatus(),
        ]);
    }
}
