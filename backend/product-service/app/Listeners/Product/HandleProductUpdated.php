<?php

namespace App\Listeners\Product;

use App\Events\ProductUpdated;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleProductUpdated implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(ProductUpdated $event): void
    {
        $this->broker->publish('product.updated', [
            'product_id' => $event->product->id,
            'tenant_id' => $event->product->tenant_id,
            'name' => $event->product->name,
            'sku' => $event->product->sku,
            'price' => $event->product->price,
        ]);
    }
}
