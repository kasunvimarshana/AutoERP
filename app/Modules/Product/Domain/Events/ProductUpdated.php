<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Product\Domain\Entities\Product;

class ProductUpdated extends BaseEvent
{
    public function __construct(public readonly Product $product)
    {
        parent::__construct($product->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'       => $this->product->getId(),
            'sku'      => $this->product->getSku()->value(),
            'name'     => $this->product->getName(),
            'status'   => $this->product->getStatus(),
            'category' => $this->product->getCategory(),
        ]);
    }
}
