<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class PurchaseOrderConfirmed extends BaseEvent {
    public int $purchaseOrderId;
    public function __construct(int $tenantId, int $purchaseOrderId) {
        parent::__construct($tenantId); $this->purchaseOrderId = $purchaseOrderId;
    }
    public function broadcastWith(): array { return array_merge(parent::broadcastWith(), ['purchaseOrderId' => $this->purchaseOrderId]); }
}
