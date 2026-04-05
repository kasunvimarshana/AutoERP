<?php
declare(strict_types=1);
namespace Modules\Supplier\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class SupplierCreated extends BaseEvent {
    public int $supplierId;
    public function __construct(int $tenantId, int $id) {
        parent::__construct($tenantId);
        $this->supplierId = $id;
    }
    public function broadcastWith(): array {
        return array_merge(parent::broadcastWith(), ['supplierId' => $this->supplierId]);
    }
}
