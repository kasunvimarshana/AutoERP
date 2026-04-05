<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class SalesOrderShipped extends BaseEvent {
    public int $salesOrderId;
    public function __construct(int $tenantId, int $salesOrderId) { parent::__construct($tenantId); $this->salesOrderId=$salesOrderId; }
    public function broadcastWith(): array { return array_merge(parent::broadcastWith(), ['salesOrderId' => $this->salesOrderId]); }
}
