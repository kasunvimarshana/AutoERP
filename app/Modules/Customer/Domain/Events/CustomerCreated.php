<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class CustomerCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $customer,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'customer_id' => is_object($this->customer) ? $this->customer->id : $this->customer,
        ]);
    }
}
