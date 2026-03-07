<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\Contracts\SagaStepInterface;
use App\Services\InventoryServiceClient;
use Illuminate\Support\Facades\Log;

class ReserveInventoryStep implements SagaStepInterface
{
    public function __construct(private readonly InventoryServiceClient $inventoryClient) {}

    public function getName(): string { return 'reserve_inventory'; }

    public function execute(Order $order, array $context = []): array
    {
        $reservations = [];

        foreach ($order->items as $item) {
            $result = $this->inventoryClient->reserveInventory(
                $item->product_id, $item->warehouse_id, $item->quantity, $order->tenant_id
            );

            if (!$result['success']) {
                foreach ($reservations as $r) {
                    try {
                        $this->inventoryClient->releaseInventory($r['product_id'], $r['warehouse_id'], $r['quantity'], $order->tenant_id);
                    } catch (\Exception $e) {
                        Log::error('Failed to release inventory during compensation', ['error' => $e->getMessage()]);
                    }
                }
                throw new \RuntimeException("Failed to reserve inventory for product {$item->product_id}: " . $result['message']);
            }

            $reservations[] = ['product_id' => $item->product_id, 'warehouse_id' => $item->warehouse_id, 'quantity' => $item->quantity];
        }

        return ['reservations' => $reservations];
    }

    public function compensate(Order $order, array $context = []): void
    {
        foreach ($context['reservations'] ?? [] as $r) {
            try {
                $this->inventoryClient->releaseInventory($r['product_id'], $r['warehouse_id'], $r['quantity'], $order->tenant_id);
            } catch (\Exception $e) {
                Log::error('ReserveInventoryStep compensate failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
