<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class GoodsReceiptPutAway extends BaseEvent {
    public int $grId;
    public function __construct(int $tenantId, int $grId) { parent::__construct($tenantId); $this->grId = $grId; }
    public function broadcastWith(): array { return array_merge(parent::broadcastWith(), ['grId' => $this->grId]); }
}
