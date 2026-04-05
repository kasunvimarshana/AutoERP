<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AccountCreated extends BaseEvent
{
    public int $accountId;

    public function __construct(int $tenantId, int $accountId)
    {
        parent::__construct($tenantId);
        $this->accountId = $accountId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['accountId' => $this->accountId]);
    }
}
