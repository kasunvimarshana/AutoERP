<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class OrganizationUnitDeleted extends BaseEvent
{
    public readonly int $unitId;

    public function __construct(int $unitId, int $tenantId)
    {
        $this->unitId = $unitId;
        parent::__construct($tenantId, $unitId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->unitId,
        ]);
    }
}
