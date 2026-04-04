<?php
namespace Modules\Pricing\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class TaxRateCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $taxRateId)
    {
        parent::__construct($tenantId);
    }
}
