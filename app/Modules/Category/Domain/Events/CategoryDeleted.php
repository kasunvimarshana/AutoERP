<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CategoryDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $categoryId,
        int $tenantId
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->categoryId,
        ]);
    }
}
