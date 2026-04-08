<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class LocationCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $location,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'location_id' => is_object($this->location) ? $this->location->id : $this->location,
        ]);
    }
}
