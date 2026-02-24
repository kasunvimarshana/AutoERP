<?php
namespace Modules\Tenant\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class TenantCreated extends DomainEvent
{
    public function __construct(public readonly string $tenantId, public readonly string $tenantName)
    {
        parent::__construct();
    }
}
