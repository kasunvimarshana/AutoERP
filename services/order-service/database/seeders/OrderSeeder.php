<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['id' => Str::uuid(), 'name' => 'Alice Johnson',  'email' => 'alice@example.com'],
            ['id' => Str::uuid(), 'name' => 'Bob Smith',      'email' => 'bob@example.com'],
            ['id' => Str::uuid(), 'name' => 'Carol Williams', 'email' => 'carol@example.com'],
        ];

        $sampleProducts = [
            ['product_id' => 1, 'product_name' => 'Widget A',   'product_sku' => 'SKU-001', 'unit_price' => 29.99],
            ['product_id' => 2, 'product_name' => 'Gadget B',   'product_sku' => 'SKU-002', 'unit_price' => 49.99],
            ['product_id' => 3, 'product_name' => 'Doohickey C','product_sku' => 'SKU-003', 'unit_price' => 14.99],
        ];

        foreach ($customers as $customer) {
            for ($i = 0; $i < 3; $i++) {
                $itemCount    = random_int(1, 3);
                $subtotal     = 0.0;
                $selectedItems = [];

                for ($j = 0; $j < $itemCount; $j++) {
                    $product  = $sampleProducts[array_rand($sampleProducts)];
                    $quantity = random_int(1, 5);
                    $total    = round($product['unit_price'] * $quantity, 2);
                    $subtotal += $total;

                    $selectedItems[] = array_merge($product, [
                        'quantity'    => $quantity,
                        'total_price' => $total,
                        'status'      => OrderItem::STATUS_CONFIRMED,
                    ]);
                }

                $taxAmount   = round($subtotal * 0.1, 2);
                $totalAmount = round($subtotal + $taxAmount, 2);

                /** @var Order $order */
                $order = Order::create([
                    'customer_id'     => $customer['id'],
                    'customer_name'   => $customer['name'],
                    'customer_email'  => $customer['email'],
                    'status'          => Order::STATUS_CONFIRMED,
                    'total_amount'    => $totalAmount,
                    'tax_amount'      => $taxAmount,
                    'discount_amount' => 0.00,
                    'shipping_address' => [
                        'street'      => '123 Seed Street',
                        'city'        => 'Seedville',
                        'state'       => 'CA',
                        'postal_code' => '90210',
                        'country'     => 'USA',
                    ],
                    'saga_status'  => Order::SAGA_COMPLETED,
                    'placed_at'    => now()->subDays(random_int(1, 30)),
                    'confirmed_at' => now()->subDays(random_int(0, 29)),
                ]);

                foreach ($selectedItems as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }
            }
        }
    }
}
