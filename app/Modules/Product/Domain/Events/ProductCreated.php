<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductCreated extends BaseEvent
{
    public int $productId;

    public function __construct(int $tenantId, int $productId)
    {
        parent::__construct($tenantId);
        $this->productId = $productId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['productId' => $this->productId]);
    }
}
