<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

class OrganizationUnitUpdated extends BaseEvent
{
    public function __construct(public readonly OrganizationUnit $unit)
    {
        parent::__construct($unit->getTenantId(), $unit->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->unit->getId(),
            'name'        => $this->unit->getName()->value(),
            'code'        => $this->unit->getCode()?->value(),
            'description' => $this->unit->getDescription(),
            'parentId'    => $this->unit->getParentId(),
        ]);
    }
}
