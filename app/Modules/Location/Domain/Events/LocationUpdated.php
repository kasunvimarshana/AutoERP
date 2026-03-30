<?php

declare(strict_types=1);

namespace Modules\Location\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Location\Domain\Entities\Location;

class LocationUpdated extends BaseEvent
{
    public function __construct(public readonly Location $location)
    {
        parent::__construct($location->getTenantId(), $location->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->location->getId(),
            'name'        => $this->location->getName()->value(),
            'code'        => $this->location->getCode()?->value(),
            'type'        => $this->location->getType(),
            'parentId'    => $this->location->getParentId(),
        ]);
    }
}
