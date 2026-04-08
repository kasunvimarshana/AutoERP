<?php

declare(strict_types=1);

namespace Modules\Account\Domain\Events;

use Modules\Account\Domain\Entities\Account;
use Modules\Core\Domain\Events\BaseEvent;

class AccountCreated extends BaseEvent
{
    public function __construct(public readonly Account $account)
    {
        parent::__construct($account->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->account->getId(),
            'code'   => $this->account->getCode(),
            'name'   => $this->account->getName(),
            'type'   => $this->account->getType(),
            'status' => $this->account->getStatus(),
        ]);
    }
}
