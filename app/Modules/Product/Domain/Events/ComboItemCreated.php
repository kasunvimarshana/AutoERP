<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Product\Domain\Entities\ComboItem;

class ComboItemCreated extends BaseEvent
{
    public function __construct(public readonly ComboItem $comboItem)
    {
        parent::__construct($comboItem->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'                   => $this->comboItem->getId(),
            'product_id'           => $this->comboItem->getProductId(),
            'component_product_id' => $this->comboItem->getComponentProductId(),
            'quantity'             => $this->comboItem->getQuantity(),
        ]);
    }
}
