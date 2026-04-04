<?php
namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ProductCategoryCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $categoryId)
    {
        parent::__construct($tenantId);
    }
}
