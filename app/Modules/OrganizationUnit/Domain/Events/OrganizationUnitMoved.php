<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

class OrganizationUnitMoved extends BaseEvent
{
    public function __construct(
        public readonly OrganizationUnit $unit,
        public readonly ?int $previousParentId,
    ) {
        parent::__construct($unit->getTenantId(), $unit->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'               => $this->unit->getId(),
            'newParentId'      => $this->unit->getParentId(),
            'previousParentId' => $this->previousParentId,
        ]);
    }
}
