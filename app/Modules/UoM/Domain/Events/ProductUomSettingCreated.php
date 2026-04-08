<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\UoM\Domain\Entities\ProductUomSetting;

class ProductUomSettingCreated extends BaseEvent
{
    public function __construct(public readonly ProductUomSetting $setting)
    {
        parent::__construct($setting->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'         => $this->setting->getId(),
            'product_id' => $this->setting->getProductId(),
        ]);
    }
}
