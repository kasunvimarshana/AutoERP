<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SupplierDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $supplierId,
        int $tenantId
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->supplierId,
        ]);
    }
}
