<?php

namespace Modules\ECommerce\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderLineRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Domain\Events\ECommerceOrderPlaced;

class PlaceECommerceOrderUseCase
{
    public function __construct(
        private ECommerceOrderRepositoryInterface     $orderRepo,
        private ECommerceOrderLineRepositoryInterface $lineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId    = $data['tenant_id'];
            $referenceNo = $this->generateReferenceNo($tenantId);
            $lines       = $data['lines'] ?? [];

            // Calculate totals using BCMath — no floating-point arithmetic
            $subtotal = '0.00000000';
            foreach ($lines as $line) {
                $unitPrice  = (string) ($line['unit_price'] ?? '0');
                $quantity   = (string) ($line['quantity'] ?? '0');
                $discount   = (string) ($line['discount'] ?? '0');
                $taxRate    = (string) ($line['tax_rate'] ?? '0');

                $gross      = bcmul($unitPrice, $quantity, 8);
                $afterDisc  = bcsub($gross, $discount, 8);
                $taxAmount  = bcmul($afterDisc, bcdiv($taxRate, '100', 8), 8);
                $lineTotal  = bcadd($afterDisc, $taxAmount, 8);

                $line['line_total'] = $lineTotal;
                $lines[array_search($line, $lines)] = $line;

                $subtotal = bcadd($subtotal, $lineTotal, 8);
            }

            $shippingCost = (string) ($data['shipping_cost'] ?? '0');
            $taxAmount    = (string) ($data['tax_amount'] ?? '0');
            $total        = bcadd(bcadd($subtotal, $taxAmount, 8), $shippingCost, 8);

            $order = $this->orderRepo->create([
                'tenant_id'        => $tenantId,
                'reference_no'     => $referenceNo,
                'customer_name'    => $data['customer_name'],
                'customer_email'   => $data['customer_email'],
                'customer_phone'   => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'status'           => 'pending',
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'shipping_cost'    => $shippingCost,
                'total'            => $total,
                'payment_method'   => $data['payment_method'] ?? null,
                'payment_status'   => 'unpaid',
                'notes'            => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $this->lineRepo->create([
                    'tenant_id'          => $tenantId,
                    'order_id'           => $order->id,
                    'product_listing_id' => $line['product_listing_id'] ?? null,
                    'product_name'       => $line['product_name'],
                    'unit_price'         => $line['unit_price'],
                    'quantity'           => $line['quantity'],
                    'discount'           => $line['discount'] ?? '0',
                    'tax_rate'           => $line['tax_rate'] ?? '0',
                    'line_total'         => $line['line_total'],
                ]);
            }

            Event::dispatch(new ECommerceOrderPlaced($order->id, $tenantId));

            return $order;
        });
    }

    private function generateReferenceNo(string $tenantId): string
    {
        $year  = now()->year;
        $count = DB::table('ec_orders')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "ECO-{$year}-{$sequence}";
    }
}
