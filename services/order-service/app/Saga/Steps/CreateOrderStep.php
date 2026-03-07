<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Models\OrderItem;
use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateOrderStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'create_order';
    }

    public function execute(array $context): array
    {
        $order = DB::transaction(function () use ($context) {
            $tenantId = $context['tenant_id'];

            $order = Order::create([
                'tenant_id'          => $tenantId,
                'order_number'       => Order::generateOrderNumber($tenantId),
                'customer_id'        => $context['customer_id'] ?? null,
                'customer_name'      => $context['customer_name'] ?? null,
                'customer_email'     => $context['customer_email'] ?? null,
                'status'             => Order::STATUS_PENDING,
                'subtotal'           => $context['subtotal'] ?? 0,
                'tax'                => $context['tax'] ?? 0,
                'discount'           => $context['discount'] ?? 0,
                'shipping_cost'      => $context['shipping_cost'] ?? 0,
                'total'              => $context['total'] ?? 0,
                'currency'           => $context['currency'] ?? 'USD',
                'shipping_address'   => $context['shipping_address'] ?? null,
                'billing_address'    => $context['billing_address'] ?? null,
                'payment_method'     => $context['payment_method'] ?? null,
                'payment_status'     => Order::PAYMENT_STATUS_PENDING,
                'notes'              => $context['notes'] ?? null,
                'metadata'           => $context['metadata'] ?? null,
                'saga_transaction_id' => $context['saga_id'] ?? null,
            ]);

            foreach ($context['items'] ?? [] as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'] ?? null,
                    'product_sku'  => $item['product_sku'] ?? null,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'subtotal'     => $item['subtotal'] ?? ($item['quantity'] * $item['unit_price']),
                    'tax'          => $item['tax'] ?? 0,
                    'discount'     => $item['discount'] ?? 0,
                    'attributes'   => $item['attributes'] ?? null,
                ]);
            }

            return $order;
        });

        Log::info('CreateOrderStep: order created', ['order_id' => $order->id]);

        $context['order']    = $order;
        $context['order_id'] = $order->id;

        return $context;
    }

    public function compensate(array $context): void
    {
        $order = $context['order'] ?? null;

        if ($order === null && isset($context['order_id'])) {
            $order = Order::find($context['order_id']);
        }

        if ($order === null) {
            Log::warning('CreateOrderStep: no order found for compensation');
            return;
        }

        DB::transaction(function () use ($order) {
            $order->status = Order::STATUS_CANCELLED;
            $order->save();
        });

        Log::info('CreateOrderStep: order cancelled during compensation', ['order_id' => $order->id]);
    }
}
