<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CustomerCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $customerId,
    ) {
        parent::__construct($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'customerId' => $this->customerId,
        ]);
    }
}
