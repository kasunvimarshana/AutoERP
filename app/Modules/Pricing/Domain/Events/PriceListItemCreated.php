<?php
namespace Modules\Pricing\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class PriceListItemCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $itemId)
    {
        parent::__construct($tenantId);
    }
}
