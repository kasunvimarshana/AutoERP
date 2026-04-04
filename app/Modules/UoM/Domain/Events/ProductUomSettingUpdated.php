<?php
namespace Modules\UoM\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class ProductUomSettingUpdated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
