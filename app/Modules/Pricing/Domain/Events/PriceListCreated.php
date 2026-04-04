<?php
namespace Modules\Pricing\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class PriceListCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $priceListId)
    {
        parent::__construct($tenantId);
    }
}
