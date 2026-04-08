<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Position;

class PositionUpdated extends BaseEvent
{
    public function __construct(public readonly Position $position)
    {
        parent::__construct($position->getTenantId(), $position->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'   => $this->position->getId(),
            'name' => $this->position->getName()->value(),
            'code' => $this->position->getCode()?->value(),
        ]);
    }
}
