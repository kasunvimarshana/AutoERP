<?php
namespace Modules\Accounting\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AccountCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $accountId)
    {
        parent::__construct($tenantId);
    }
}
