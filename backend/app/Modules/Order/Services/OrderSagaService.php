<?php

namespace App\Modules\Order\Services;

use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSagaService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function execute(array $orderData): Order
    {
        $reservedItems = [];

        DB::beginTransaction();
        try {
            $this->validateInventory($orderData['items']);

            $enrichedItems   = $this->enrichItemsWithPrices($orderData['items']);
            $reservedItems   = $this->reserveInventory($enrichedItems);
            $orderData['items'] = $enrichedItems;

            $order = $this->orderRepository->create($orderData);
            $this->orderRepository->updateStatus($order->id, 'confirmed');
            $order->refresh();

            DB::commit();
            return $order;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->releaseInventory($reservedItems);
            Log::error('Order saga failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function validateInventory(array $items): void
    {
        foreach ($items as $item) {
            $inventory = $this->inventoryRepository->findByProduct($item['product_id']);
            if (!$inventory || ($inventory->quantity - $inventory->reserved_quantity) < $item['quantity']) {
                throw new \RuntimeException("Insufficient inventory for product {$item['product_id']}");
            }
        }
    }

    private function enrichItemsWithPrices(array $items): array
    {
        return array_map(function ($item) {
            $product = $this->productRepository->find($item['product_id']);
            if (!$product) {
                throw new \RuntimeException("Product {$item['product_id']} not found");
            }
            $item['unit_price'] = (float) $product->price;
            return $item;
        }, $items);
    }

    private function reserveInventory(array $items): array
    {
        $reserved = [];
        foreach ($items as $item) {
            $inventory = $this->inventoryRepository->findByProduct($item['product_id']);
            $this->inventoryRepository->adjustQuantity($inventory->id, -$item['quantity'], 'order_reservation');
            $reserved[] = ['inventory_id' => $inventory->id, 'quantity' => $item['quantity']];
        }
        return $reserved;
    }

    private function releaseInventory(array $reservedItems): void
    {
        foreach ($reservedItems as $item) {
            try {
                $this->inventoryRepository->adjustQuantity($item['inventory_id'], $item['quantity'], 'order_compensation');
            } catch (\Throwable $e) {
                Log::critical('Failed to release inventory during saga compensation', $item);
            }
        }
    }
}
