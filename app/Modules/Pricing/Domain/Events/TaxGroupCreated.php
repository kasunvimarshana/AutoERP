<?php
namespace Modules\Pricing\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class TaxGroupCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $taxGroupId)
    {
        parent::__construct($tenantId);
    }
}
