<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SupplierCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $supplierId,
    ) {
        parent::__construct($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'supplierId' => $this->supplierId,
        ]);
    }
}
