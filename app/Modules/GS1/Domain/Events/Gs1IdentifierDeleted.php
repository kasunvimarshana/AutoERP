<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\GS1\Domain\Entities\Gs1Identifier;

class Gs1IdentifierDeleted extends BaseEvent
{
    public function __construct(public readonly Gs1Identifier $identifier)
    {
        parent::__construct($identifier->getTenantId(), $identifier->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->identifier->getId(),
            'tenant_id' => $this->identifier->getTenantId(),
        ]);
    }
}
