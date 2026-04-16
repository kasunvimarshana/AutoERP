<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantDeleted extends BaseEvent
{
    public function __construct(int $tenantId)
    {
        parent::__construct($tenantId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->tenantId,
        ]);
    }
}
