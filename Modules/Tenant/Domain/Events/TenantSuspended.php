<?php
namespace Modules\Tenant\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class TenantSuspended extends DomainEvent
{
    public function __construct(public readonly string $tenantId)
    {
        parent::__construct();
    }
}
