<?php
declare(strict_types=1);
namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantUpdated extends BaseEvent
{
    public int $tenantEntityId;

    public function __construct(int $tenantId, int $tenantEntityId)
    {
        parent::__construct($tenantId);
        $this->tenantEntityId = $tenantEntityId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['tenantEntityId' => $this->tenantEntityId]);
    }
}
