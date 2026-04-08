<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $productId,
        int $tenantId
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->productId,
        ]);
    }
}
