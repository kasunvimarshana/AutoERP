<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AccountCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $accountId,
    ) {
        parent::__construct($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'accountId' => $this->accountId,
        ]);
    }
}
