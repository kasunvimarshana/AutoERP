<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\Events\InventoryUpdated;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class PublishInventoryUpdatedEvent
{
    public function __construct(private RabbitMQService $rabbitMQService) {}

    public function handle(InventoryUpdated $event): void
    {
        try {
            $this->rabbitMQService->publish(
                config('rabbitmq.exchange'),
                'inventory.updated',
                [
                    'event' => 'InventoryUpdated',
                    'action' => $event->action,
                    'data' => $event->inventoryItem->toArray(),
                    'context' => $event->context,
                    'timestamp' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to publish InventoryUpdated event: ' . $e->getMessage());
        }
    }
}
