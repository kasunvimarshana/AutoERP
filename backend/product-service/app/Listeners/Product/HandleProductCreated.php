<?php

namespace App\Listeners\Product;

use App\Events\ProductCreated;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleProductCreated implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(ProductCreated $event): void
    {
        $this->broker->publish('product.created', [
            'product_id' => $event->product->id,
            'tenant_id' => $event->product->tenant_id,
            'name' => $event->product->name,
            'sku' => $event->product->sku,
            'price' => $event->product->price,
            'category_id' => $event->product->category_id,
        ]);
    }
}
