<?php
namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductVariantCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $variantId)
    {
        parent::__construct($tenantId);
    }
}
