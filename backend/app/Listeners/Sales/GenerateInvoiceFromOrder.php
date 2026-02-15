<?php

declare(strict_types=1);

namespace App\Listeners\Sales;

use App\Events\Sales\OrderApproved;
use App\Events\Invoice\InvoiceGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceFromOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderApproved $event): void
    {
        // Generate invoice from approved sales order
        DB::transaction(function () use ($event) {
            $order = $event->order;
            
            // Check if invoice already exists
            if ($order->invoice_id) {
                return;
            }

            // Create invoice
            $invoice = \App\Modules\Invoice\Models\Invoice::create([
                'tenant_id' => $event->tenantId,
                'organization_id' => $order->organization_id,
                'branch_id' => $order->branch_id,
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'invoice_number' => $this->generateInvoiceNumber($event->tenantId),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'currency_code' => $order->currency_code,
                'status' => 'draft',
                'notes' => "Generated from Sales Order #{$order->order_number}",
                'created_by' => $event->userId,
            ]);

            // Copy order items to invoice items
            foreach ($order->items as $orderItem) {
                $invoice->items()->create([
                    'product_id' => $orderItem->product_id,
                    'description' => $orderItem->description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'tax_rate' => $orderItem->tax_rate,
                    'tax_amount' => $orderItem->tax_amount,
                    'discount_percentage' => $orderItem->discount_percentage,
                    'discount_amount' => $orderItem->discount_amount,
                    'line_total' => $orderItem->line_total,
                ]);
            }

            // Update order with invoice reference
            $order->update(['invoice_id' => $invoice->id]);

            // Dispatch invoice generated event
            event(new InvoiceGenerated($invoice, $event->tenantId, $event->userId));
        });
    }

    private function generateInvoiceNumber(int $tenantId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $count = \App\Modules\Invoice\Models\Invoice::where('tenant_id', $tenantId)
            ->whereDate('created_at', now())
            ->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}
