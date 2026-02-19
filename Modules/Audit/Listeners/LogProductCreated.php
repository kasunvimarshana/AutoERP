<?php

declare(strict_types=1);

namespace Modules\Audit\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Audit\Services\AuditService;
use Modules\Product\Events\ProductCreated;

class LogProductCreated implements ShouldQueue
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(ProductCreated $event): void
    {
        $this->auditService->log([
            'event' => 'product.created',
            'auditable_type' => get_class($event->product),
            'auditable_id' => $event->product->id,
            'old_values' => [],
            'new_values' => $event->product->toArray(),
            'metadata' => [
                'product_name' => $event->product->name,
                'product_type' => $event->product->type,
            ],
        ]);
    }
}
