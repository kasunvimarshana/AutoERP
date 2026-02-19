<?php

declare(strict_types=1);

namespace Modules\Audit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Audit\Services\AuditService;
use Modules\Pricing\Events\PriceCreated;

class LogPriceCreated implements ShouldQueue
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(PriceCreated $event): void
    {
        $this->auditService->log([
            'event' => 'price.created',
            'auditable_type' => get_class($event->price),
            'auditable_id' => $event->price->id,
            'old_values' => [],
            'new_values' => $event->price->toArray(),
            'metadata' => [
                'product_id' => $event->price->product_id,
                'price_list_id' => $event->price->price_list_id,
                'price' => $event->price->price,
            ],
        ]);
    }
}
