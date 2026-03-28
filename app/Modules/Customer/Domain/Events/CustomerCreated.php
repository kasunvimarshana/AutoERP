<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Customer\Domain\Entities\Customer;

class CustomerCreated extends BaseEvent
{
    public function __construct(public readonly Customer $customer)
    {
        parent::__construct($customer->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->customer->getId(),
            'name'   => $this->customer->getName(),
            'code'   => $this->customer->getCode(),
            'status' => $this->customer->getStatus(),
        ]);
    }
}
