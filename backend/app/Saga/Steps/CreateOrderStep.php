<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Saga\Contracts\SagaInterface;
use Illuminate\Support\Facades\DB;

class CreateOrderStep implements SagaInterface
{
    public function getName(): string
    {
        return 'CreateOrder';
    }

    public function execute(array &$context): array
    {
        DB::beginTransaction();

        try {
            // Build order items with prices
            $itemsData = [];
            $subtotal  = 0;

            foreach ($context['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price   = $item['unit_price'] ?? $product->price;
                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $price,
                    'subtotal'   => $lineTotal,
                    'tax'        => 0,
                    'discount'   => 0,
                    'total'      => $lineTotal,
                ];
            }

            $tax      = $context['tax']      ?? 0;
            $discount = $context['discount'] ?? 0;
            $total    = $subtotal + $tax - $discount;

            $order = Order::create([
                'tenant_id'        => app()->bound('current_tenant') ? app('current_tenant')->id : null,
                'user_id'          => $context['user_id'],
                'order_number'     => $context['order_number'],
                'status'           => Order::STATUS_PENDING,
                'payment_status'   => Order::PAYMENT_PENDING,
                'subtotal'         => $subtotal,
                'tax'              => $tax,
                'discount'         => $discount,
                'total'            => $total,
                'currency'         => $context['currency']         ?? 'USD',
                'notes'            => $context['notes']            ?? null,
                'shipping_address' => $context['shipping_address'] ?? null,
                'billing_address'  => $context['billing_address']  ?? null,
                'saga_id'          => $context['saga_id']          ?? null,
            ]);

            foreach ($itemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            DB::commit();

            $context['order']    = $order->load('items');
            $context['order_id'] = $order->id;

            return $context;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function compensate(array &$context): void
    {
        if (isset($context['order_id'])) {
            Order::withoutGlobalScope('tenant')
                 ->where('id', $context['order_id'])
                 ->delete();

            unset($context['order'], $context['order_id']);
        }
    }
}
