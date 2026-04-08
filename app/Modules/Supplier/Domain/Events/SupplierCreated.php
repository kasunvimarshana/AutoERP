<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Supplier\Domain\Entities\Supplier;

class SupplierCreated extends BaseEvent
{
    public function __construct(public readonly Supplier $supplier)
    {
        parent::__construct($supplier->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->supplier->getId(),
            'name'   => $this->supplier->getName(),
            'code'   => $this->supplier->getCode(),
            'status' => $this->supplier->getStatus(),
        ]);
    }
}
