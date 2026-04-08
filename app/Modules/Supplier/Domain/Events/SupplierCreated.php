<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class SupplierCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $supplier,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'supplier_id' => is_object($this->supplier) ? $this->supplier->id : $this->supplier,
        ]);
    }
}
