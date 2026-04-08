<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantCreated extends BaseEvent
{
    public function __construct(public readonly Tenant $tenant)
    {
        parent::__construct($tenant->getId() ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->tenant->getId(),
            'name'   => $this->tenant->getName(),
            'domain' => $this->tenant->getDomain(),
            'active' => $this->tenant->isActive(),
        ]);
    }
}
