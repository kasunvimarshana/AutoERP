<?php

declare(strict_types=1);

namespace Modules\Procurement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Procurement\Events\GoodsReceived;

/**
 * Create AP Invoice Listener
 *
 * Creates accounts payable invoice when goods are received from suppliers.
 * Implements event-driven integration between Procurement and Accounting modules.
 */
class CreateAPInvoiceListener implements ShouldQueue
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
    public function handle(GoodsReceived $event): void
    {
        DB::beginTransaction();
        try {
            $goodsReceipt = $event->goodsReceipt;

            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = 0;

            foreach ($goodsReceipt->lines as $line) {
                $lineTotal = $line->received_quantity * $line->unit_price;
                $lineDiscount = $lineTotal * ($line->discount_percentage ?? 0) / 100;
                $lineTax = ($lineTotal - $lineDiscount) * ($line->tax_rate ?? 0) / 100;

                $subtotal += $lineTotal;
                $discountAmount += $lineDiscount;
                $taxAmount += $lineTax;
            }

            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Create AP invoice from goods receipt
            $invoice = $this->invoiceRepository->create([
                'tenant_id' => $goodsReceipt->tenant_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_type' => 'purchase',
                'invoice_date' => $goodsReceipt->receipt_date ?? now(),
                'due_date' => $this->calculateDueDate($goodsReceipt),
                'supplier_id' => $goodsReceipt->supplier_id,
                'reference_type' => 'goods_receipt',
                'reference_id' => $goodsReceipt->id,
                'reference_number' => $goodsReceipt->receipt_number,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'amount_due' => $totalAmount,
                'status' => 'pending',
                'currency' => $goodsReceipt->currency ?? 'USD',
                'notes' => "AP Invoice for goods receipt {$goodsReceipt->receipt_number}",
            ]);

            // Create invoice lines from goods receipt lines
            foreach ($goodsReceipt->lines as $line) {
                $this->invoiceLineRepository->create([
                    'tenant_id' => $goodsReceipt->tenant_id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'description' => $line->description ?? $line->product->name,
                    'quantity' => $line->received_quantity,
                    'unit_price' => $line->unit_price,
                    'discount_percentage' => $line->discount_percentage ?? 0,
                    'tax_rate' => $line->tax_rate ?? 0,
                    'line_total' => $line->received_quantity * $line->unit_price,
                ]);
            }

            DB::commit();

            Log::info('AP invoice created for goods receipt', [
                'goods_receipt_id' => $goodsReceipt->id,
                'receipt_number' => $goodsReceipt->receipt_number,
                'supplier_id' => $goodsReceipt->supplier_id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create AP invoice for goods receipt', [
                'goods_receipt_id' => $event->goodsReceipt->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate due date based on payment terms
     */
    private function calculateDueDate($goodsReceipt): \Carbon\Carbon
    {
        // Default payment terms: 30 days
        $paymentTermsDays = $goodsReceipt->purchaseOrder->payment_terms_days ?? 30;
        $receiptDate = $goodsReceipt->receipt_date ?? now();

        return \Carbon\Carbon::parse($receiptDate)->addDays($paymentTermsDays);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'APINV';
        $date = now()->format('Ymd');
        $random = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Handle a job failure
     */
    public function failed(GoodsReceived $event, \Throwable $exception): void
    {
        Log::error('AP invoice creation for goods receipt failed permanently', [
            'goods_receipt_id' => $event->goodsReceipt->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
