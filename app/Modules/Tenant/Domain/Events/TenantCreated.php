<?php
declare(strict_types=1);
namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantCreated extends BaseEvent
{
    public int $tenantEntityId;
    public string $tenantName;

    public function __construct(int $tenantId, int $tenantEntityId, string $tenantName)
    {
        parent::__construct($tenantId);
        $this->tenantEntityId = $tenantEntityId;
        $this->tenantName = $tenantName;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'tenantEntityId' => $this->tenantEntityId,
            'tenantName' => $this->tenantName,
        ]);
    }
}
