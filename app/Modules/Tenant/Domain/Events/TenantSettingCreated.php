<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Tenant\Domain\Entities\TenantSetting;

class TenantSettingCreated extends BaseEvent
{
    public function __construct(public readonly TenantSetting $setting)
    {
        parent::__construct($setting->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->setting->getId(),
            'tenant_id' => $this->setting->getTenantId(),
            'key' => $this->setting->getKey(),
        ]);
    }
}
