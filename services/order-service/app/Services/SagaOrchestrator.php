<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SagaOrchestrator
{
    public function __construct(
        private ProductServiceClient $productClient,
        private InventoryServiceClient $inventoryClient
    ) {}

    public function createOrderSaga(array $orderData, string $token): array
    {
        $sagaLog = [];
        $reservedInventories = [];
        $order = null;

        // Step 1: Create order record
        try {
            $order = Order::create([
                'user_id'       => $orderData['user_id'],
                'user_email'    => $orderData['user_email'],
                'status'        => 'pending',
                'saga_status'   => 'pending',
                'saga_step'     => 'create_order',
                'notes'         => $orderData['notes'] ?? null,
                'total_amount'  => 0,
            ]);
            $sagaLog[] = ['step' => 'create_order', 'status' => 'success'];
        } catch (\Exception $e) {
            Log::error('Saga step create_order failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'order'   => null,
                'message' => 'Failed to create order record: ' . $e->getMessage(),
            ];
        }

        // Step 2: Validate Products
        $order->update(['saga_step' => 'validate_products']);
        $validatedItems = [];
        foreach ($orderData['items'] as $item) {
            $product = $this->productClient->getProduct($item['product_id']);
            if (!$product) {
                $reason = "Product ID {$item['product_id']} not found";
                $sagaLog[] = ['step' => 'validate_products', 'status' => 'failed', 'detail' => $reason];
                $this->compensate($order, $reservedInventories, $token, $reason);
                return [
                    'success' => false,
                    'order'   => $order->fresh(),
                    'message' => 'Order creation failed - product not found',
                    'data'    => ['order' => $order->fresh()->load('items'), 'saga_log' => $sagaLog],
                ];
            }
            $validatedItems[] = [
                'product_id'       => $item['product_id'],
                'quantity'         => $item['quantity'],
                'product_name'     => $product['name'] ?? 'Unknown',
                'product_code'     => $product['code'] ?? $product['sku'] ?? '',
                'product_category' => $product['category'] ?? $product['category_name'] ?? '',
                'unit_price'       => (float) ($product['price'] ?? 0),
            ];
        }
        $sagaLog[] = [
            'step'   => 'validate_products',
            'status' => 'success',
            'detail' => count($validatedItems) . ' products validated',
        ];

        // Step 3: Reserve Inventory
        $order->update(['saga_step' => 'reserve_inventory']);
        $orderItemsData = [];
        foreach ($validatedItems as $item) {
            $inventory = $this->inventoryClient->getInventoryByProductId($item['product_id']);
            if (!$inventory) {
                $reason = "No inventory found for product {$item['product_name']}";
                $sagaLog[] = ['step' => 'reserve_inventory', 'status' => 'failed', 'detail' => $reason];
                $this->compensate($order, $reservedInventories, $token, $reason);
                return [
                    'success' => false,
                    'order'   => $order->fresh(),
                    'message' => 'Order creation failed - inventory not found',
                    'data'    => [
                        'order'    => $order->fresh()->load('items'),
                        'saga_log' => $sagaLog,
                    ],
                ];
            }

            $inventoryId = $inventory['id'];
            $result = $this->inventoryClient->reserveInventory($inventoryId, $item['quantity'], $token);

            if (!$result['success']) {
                $reason = "Insufficient inventory for product {$item['product_name']}";
                $sagaLog[] = [
                    'step'   => 'reserve_inventory',
                    'status' => 'failed',
                    'detail' => "Insufficient quantity for {$item['product_code']}",
                ];
                $this->compensate($order, $reservedInventories, $token, $reason);
                return [
                    'success' => false,
                    'order'   => $order->fresh(),
                    'message' => 'Order creation failed - insufficient inventory',
                    'data'    => [
                        'order'    => $order->fresh()->load('items'),
                        'saga_log' => $sagaLog,
                    ],
                ];
            }

            $reservedInventories[] = ['inventory_id' => $inventoryId, 'quantity' => $item['quantity']];
            $orderItemsData[] = array_merge($item, [
                'inventory_id' => $inventoryId,
                'subtotal'     => $item['unit_price'] * $item['quantity'],
            ]);
        }
        $sagaLog[] = [
            'step'   => 'reserve_inventory',
            'status' => 'success',
            'detail' => count($orderItemsData) . ' items reserved',
        ];

        // Step 4: Calculate Total
        $order->update(['saga_step' => 'calculate_total']);
        $total = array_sum(array_column($orderItemsData, 'subtotal'));

        // Step 5: Confirm Order
        $order->update(['saga_step' => 'confirm_order']);
        try {
            DB::transaction(function () use ($order, $orderItemsData, $total) {
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id'         => $order->id,
                        'product_id'       => $itemData['product_id'],
                        'product_name'     => $itemData['product_name'],
                        'product_code'     => $itemData['product_code'],
                        'product_category' => $itemData['product_category'],
                        'quantity'         => $itemData['quantity'],
                        'unit_price'       => $itemData['unit_price'],
                        'subtotal'         => $itemData['subtotal'],
                        'inventory_id'     => $itemData['inventory_id'],
                    ]);
                }
                $order->update([
                    'status'       => 'confirmed',
                    'saga_status'  => 'completed',
                    'saga_step'    => null,
                    'total_amount' => $total,
                ]);
            });
            $sagaLog[] = ['step' => 'confirm_order', 'status' => 'success'];
        } catch (\Exception $e) {
            $reason = 'Failed to confirm order: ' . $e->getMessage();
            $sagaLog[] = ['step' => 'confirm_order', 'status' => 'failed', 'detail' => $reason];
            $this->compensate($order, $reservedInventories, $token, $reason);
            return [
                'success' => false,
                'order'   => $order->fresh(),
                'message' => 'Order confirmation failed',
                'data'    => ['order' => $order->fresh()->load('items'), 'saga_log' => $sagaLog],
            ];
        }

        return [
            'success' => true,
            'order'   => $order->fresh()->load('items'),
            'message' => 'Order created successfully via Saga',
            'data'    => [
                'order'    => $order->fresh()->load('items'),
                'saga_log' => $sagaLog,
            ],
        ];
    }

    public function cancelOrderSaga(Order $order, string $token): array
    {
        $sagaLog = [];

        // Step 1: Set order to cancelling
        $order->update(['status' => 'processing', 'saga_status' => 'compensating', 'saga_step' => 'cancel_order']);
        $sagaLog[] = ['step' => 'initiate_cancel', 'status' => 'success'];

        // Step 2: Release all reserved inventory
        $released = 0;
        foreach ($order->items as $item) {
            if ($item->inventory_id) {
                $result = $this->inventoryClient->releaseInventory($item->inventory_id, $item->quantity, $token);
                if ($result['success']) {
                    $released++;
                }
            }
        }
        $sagaLog[] = [
            'step'   => 'release_inventory',
            'status' => 'success',
            'detail' => "Released {$released} reservations",
        ];

        // Step 3: Set order to cancelled
        $order->update([
            'status'      => 'cancelled',
            'saga_status' => 'compensated',
            'saga_step'   => null,
        ]);
        $sagaLog[] = ['step' => 'cancel_order', 'status' => 'success'];

        return [
            'success'  => true,
            'order'    => $order->fresh()->load('items'),
            'message'  => 'Order cancelled successfully',
            'data'     => ['order' => $order->fresh()->load('items'), 'saga_log' => $sagaLog],
        ];
    }

    private function compensate(Order $order, array $reservedInventories, string $token, string $reason): void
    {
        $order->update(['saga_status' => 'compensating', 'saga_step' => 'compensate']);
        $released = 0;
        foreach ($reservedInventories as $reservation) {
            $result = $this->inventoryClient->releaseInventory(
                $reservation['inventory_id'],
                $reservation['quantity'],
                $token
            );
            if ($result['success']) {
                $released++;
            }
        }
        $order->update([
            'status'               => 'failed',
            'saga_status'          => 'compensated',
            'saga_step'            => null,
            'compensation_reason'  => $reason,
        ]);
        Log::info("Saga compensated: released {$released} reservations. Reason: {$reason}");
    }
}
