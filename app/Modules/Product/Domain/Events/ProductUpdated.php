<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductUpdated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $productId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['productId' => $this->productId]);
    }
}
