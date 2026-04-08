<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CustomerDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $customerId,
        int $tenantId
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->customerId,
        ]);
    }
}
