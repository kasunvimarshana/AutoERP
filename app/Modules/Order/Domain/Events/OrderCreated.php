<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class OrderCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $model,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'order_id' => is_object($this->model) ? $this->model->id : $this->model,
        ]);
    }
}
