<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Pricing\Domain\Entities\PriceListItem;

class PriceListItemUpdated extends BaseEvent
{
    public function __construct(public readonly PriceListItem $priceListItem)
    {
        parent::__construct($priceListItem->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'            => $this->priceListItem->getId(),
            'tenant_id'     => $this->priceListItem->getTenantId(),
            'price_list_id' => $this->priceListItem->getPriceListId(),
        ]);
    }
}
