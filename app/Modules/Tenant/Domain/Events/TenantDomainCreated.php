<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Tenant\Domain\Entities\TenantDomain;

class TenantDomainCreated extends BaseEvent
{
    public function __construct(public readonly TenantDomain $tenantDomain)
    {
        parent::__construct($tenantDomain->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->tenantDomain->getId(),
            'tenant_id' => $this->tenantDomain->getTenantId(),
            'domain' => $this->tenantDomain->getDomain(),
            'is_primary' => $this->tenantDomain->isPrimary(),
        ]);
    }
}
