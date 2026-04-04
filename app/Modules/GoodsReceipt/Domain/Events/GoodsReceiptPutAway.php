<?php
namespace Modules\GoodsReceipt\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class GoodsReceiptPutAway extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $grId)
    {
        parent::__construct($tenantId);
    }
}
