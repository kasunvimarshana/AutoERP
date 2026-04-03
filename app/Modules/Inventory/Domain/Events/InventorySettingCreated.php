<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventorySetting;

class InventorySettingCreated extends BaseEvent
{
    public function __construct(public readonly InventorySetting $setting)
    {
        parent::__construct($setting->getTenantId(), $setting->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->setting->getId(),
            'tenant_id' => $this->setting->getTenantId(),
        ]);
    }
}
