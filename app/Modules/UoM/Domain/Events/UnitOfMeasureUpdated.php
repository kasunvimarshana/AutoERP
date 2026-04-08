<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

class UnitOfMeasureUpdated extends BaseEvent
{
    public function __construct(public readonly UnitOfMeasure $unit)
    {
        parent::__construct($unit->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->unit->getId(),
            'name'   => $this->unit->getName(),
            'code'   => $this->unit->getCode(),
            'symbol' => $this->unit->getSymbol(),
        ]);
    }
}
