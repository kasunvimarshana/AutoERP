<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Taxation\Domain\Entities\TaxRate;

class TaxRateActivated extends BaseEvent
{
    public function __construct(public readonly TaxRate $taxRate)
    {
        parent::__construct($taxRate->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->taxRate->getId(),
            'tenant_id' => $this->taxRate->getTenantId(),
        ]);
    }
}
