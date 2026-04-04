<?php
namespace Modules\Configuration\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class SystemSettingChanged extends BaseEvent
{
    public function __construct(int $tenantId, public readonly string $settingKey)
    {
        parent::__construct($tenantId);
    }
}
