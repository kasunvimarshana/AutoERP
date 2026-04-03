<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Settings\Domain\Entities\Setting;

class SettingUpdated extends BaseEvent
{
    public function __construct(public readonly Setting $setting)
    {
        parent::__construct($setting->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->setting->getId(),
            'tenant_id' => $this->setting->getTenantId(),
        ]);
    }
}
