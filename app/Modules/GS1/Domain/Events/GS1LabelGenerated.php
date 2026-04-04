<?php
namespace Modules\GS1\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class GS1LabelGenerated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
