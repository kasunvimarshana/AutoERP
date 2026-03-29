<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Product\Domain\Entities\ProductVariation;

class ProductVariationCreated extends BaseEvent
{
    public function __construct(public readonly ProductVariation $variation)
    {
        parent::__construct($variation->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'         => $this->variation->getId(),
            'product_id' => $this->variation->getProductId(),
            'sku'        => $this->variation->getSku()->value(),
            'name'       => $this->variation->getName(),
        ]);
    }
}
