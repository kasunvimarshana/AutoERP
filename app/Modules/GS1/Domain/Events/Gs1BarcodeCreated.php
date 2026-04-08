<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\GS1\Domain\Entities\Gs1Barcode;

class Gs1BarcodeCreated extends BaseEvent
{
    public function __construct(public readonly Gs1Barcode $barcode)
    {
        parent::__construct($barcode->getTenantId(), $barcode->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->barcode->getId(),
            'tenant_id' => $this->barcode->getTenantId(),
        ]);
    }
}
