<?php

namespace App\Modules\Inventory\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Modules\Inventory\Services\Contracts\InventoryServiceInterface;

class ConsumeProductCreatedEvent
{
    private InventoryServiceInterface $inventoryService;

    public function __construct(InventoryServiceInterface $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Handle the payload from RabbitMQ.
     * In a real microservice architecture, you'd use a generic handler or laravel-rabbitmq's 
     * Job pattern to consume queue messages and map them to local events/listeners.
     */
    public function handle(array $payload): void
    {
        Log::info('Consuming ProductCreated from RabbitMQ', ['payload' => $payload]);

        $productData = $payload['data'];

        // Product ID from external product-service.
        $externalProductId = $productData['id'];

        // Initialize inventory with 0 stock
        // Compensating transactions or Sagas can be handled here on failure if necessary.
        $this->inventoryService->initializeInventory($externalProductId);
    }
}
