<?php

declare(strict_types=1);

namespace Modules\Account\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AccountDeleted extends BaseEvent
{
    public function __construct(public readonly int $accountId, int $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->accountId,
        ]);
    }
}
