<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class BrandDeleted extends BaseEvent
{
    public function __construct(public readonly int $brandId, int $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->brandId,
        ]);
    }
}
