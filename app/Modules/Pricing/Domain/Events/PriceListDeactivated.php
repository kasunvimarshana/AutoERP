<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Pricing\Domain\Entities\PriceList;

class PriceListDeactivated extends BaseEvent
{
    public function __construct(public readonly PriceList $priceList)
    {
        parent::__construct($priceList->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->priceList->getId(),
            'tenant_id' => $this->priceList->getTenantId(),
        ]);
    }
}
