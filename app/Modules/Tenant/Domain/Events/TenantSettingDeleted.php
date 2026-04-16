<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantSettingDeleted extends BaseEvent
{
    public function __construct(
        int $tenantId,
        private readonly int $settingId
    )
    {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->settingId,
        ]);
    }
}
