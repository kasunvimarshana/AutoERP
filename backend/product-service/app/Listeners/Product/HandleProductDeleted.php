<?php

namespace App\Listeners\Product;

use App\Events\ProductDeleted;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleProductDeleted implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(ProductDeleted $event): void
    {
        $this->broker->publish('product.deleted', [
            'product_id' => $event->product->id,
            'tenant_id' => $event->product->tenant_id,
            'sku' => $event->product->sku,
        ]);
    }
}
