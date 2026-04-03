<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Taxation\Domain\Entities\TaxRule;

class TaxRuleUpdated extends BaseEvent
{
    public function __construct(public readonly TaxRule $taxRule)
    {
        parent::__construct($taxRule->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->taxRule->getId(),
            'tenant_id' => $this->taxRule->getTenantId(),
        ]);
    }
}
