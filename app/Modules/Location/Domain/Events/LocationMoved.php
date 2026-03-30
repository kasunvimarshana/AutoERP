<?php

declare(strict_types=1);

namespace Modules\Location\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Location\Domain\Entities\Location;

class LocationMoved extends BaseEvent
{
    public function __construct(
        public readonly Location $location,
        public readonly ?int $oldParentId
    ) {
        parent::__construct($location->getTenantId(), $location->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->location->getId(),
            'parentId'    => $this->location->getParentId(),
            'oldParentId' => $this->oldParentId,
        ]);
    }
}
