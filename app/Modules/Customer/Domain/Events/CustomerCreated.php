<?php
declare(strict_types=1);
namespace Modules\Customer\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class CustomerCreated extends BaseEvent {
    public int $customerId;
    public function __construct(int $tenantId, int $id) {
        parent::__construct($tenantId);
        $this->customerId = $id;
    }
    public function broadcastWith(): array {
        return array_merge(parent::broadcastWith(), ['customerId' => $this->customerId]);
    }
}
