<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\UoM\Domain\Entities\UomConversion;

class UomConversionUpdated extends BaseEvent
{
    public function __construct(public readonly UomConversion $conversion)
    {
        parent::__construct($conversion->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->conversion->getId(),
            'from_uom_id' => $this->conversion->getFromUomId(),
            'to_uom_id'   => $this->conversion->getToUomId(),
            'factor'      => $this->conversion->getFactor(),
        ]);
    }
}
