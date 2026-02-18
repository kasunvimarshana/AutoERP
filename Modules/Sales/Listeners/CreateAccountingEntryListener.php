<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Sales\Events\SalesOrderConfirmed;

/**
 * Create Accounting Entry Listener
 *
 * Creates an accounts receivable invoice when a sales order is confirmed.
 * Implements event-driven integration between Sales and Accounting modules.
 */
class CreateAccountingEntryListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Create a new listener instance
     */
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceLineRepositoryInterface $invoiceLineRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(SalesOrderConfirmed $event): void
    {
        DB::beginTransaction();
        try {
            $order = $event->salesOrder;

            // Create AR invoice from sales order
            $invoice = $this->invoiceRepository->create([
                'tenant_id' => $order->tenant_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_type' => 'sales',
                'invoice_date' => now(),
                'due_date' => now()->addDays(30), // Default 30 days payment terms
                'customer_id' => $order->customer_id,
                'reference_type' => 'sales_order',
                'reference_id' => $order->id,
                'reference_number' => $order->order_number,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'amount_paid' => 0,
                'amount_due' => $order->total_amount,
                'status' => 'draft',
                'currency' => $order->currency ?? 'USD',
                'notes' => "Invoice for sales order {$order->order_number}",
            ]);

            // Create invoice lines from order lines
            foreach ($order->lines as $line) {
                $this->invoiceLineRepository->create([
                    'tenant_id' => $order->tenant_id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_percentage' => $line->discount_percentage,
                    'tax_rate' => $line->tax_rate,
                    'line_total' => $line->line_total,
                ]);
            }

            DB::commit();

            Log::info('Accounting invoice created for sales order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create accounting invoice for sales order', [
                'order_id' => $event->salesOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Handle a job failure
     */
    public function failed(SalesOrderConfirmed $event, \Throwable $exception): void
    {
        Log::error('Accounting invoice creation failed permanently', [
            'order_id' => $event->salesOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
