<?php
namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $productId)
    {
        parent::__construct($tenantId);
    }
}
