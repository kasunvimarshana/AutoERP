<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ComboItemDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $comboItemId,
        int $tenantId
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->comboItemId,
        ]);
    }
}
