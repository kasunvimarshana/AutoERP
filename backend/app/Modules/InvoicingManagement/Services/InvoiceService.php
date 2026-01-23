<?php

namespace App\Modules\InvoicingManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InvoicingManagement\Events\InvoiceCreated;
use App\Modules\InvoicingManagement\Events\InvoiceGenerated;
use App\Modules\InvoicingManagement\Events\InvoiceSent;
use App\Modules\InvoicingManagement\Events\InvoicePaid;
use App\Modules\InvoicingManagement\Repositories\InvoiceRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After invoice creation hook
     */
    protected function afterCreate(Model $invoice, array $data): void
    {
        $invoice->invoice_number = $this->generateInvoiceNumber($invoice->id);
        $invoice->save();
        
        event(new InvoiceCreated($invoice));
    }

    /**
     * Generate invoice from job card
     */
    public function generateFromJobCard(int $jobCardId, array $additionalData = []): Model
    {
        try {
            DB::beginTransaction();

            // Fetch job card details
            $jobCard = app(\App\Modules\JobCardManagement\Repositories\JobCardRepository::class)->findOrFail($jobCardId);

            $invoiceData = array_merge([
                'job_card_id' => $jobCardId,
                'customer_id' => $jobCard->customer_id,
                'vehicle_id' => $jobCard->vehicle_id,
                'status' => 'draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
            ], $additionalData);

            $invoice = $this->create($invoiceData);

            event(new InvoiceGenerated($invoice, $jobCard));

            DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send invoice to customer
     */
    public function send(int $invoiceId, ?string $email = null): Model
    {
        try {
            DB::beginTransaction();

            $invoice = $this->repository->findOrFail($invoiceId);
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();

            event(new InvoiceSent($invoice, $email));

            DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(int $invoiceId, array $paymentData = []): Model
    {
        try {
            DB::beginTransaction();

            $invoice = $this->repository->findOrFail($invoiceId);
            $invoice->status = 'paid';
            $invoice->paid_at = $paymentData['paid_at'] ?? now();
            $invoice->payment_method = $paymentData['payment_method'] ?? null;
            $invoice->save();

            event(new InvoicePaid($invoice));

            DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark invoice as overdue
     */
    public function markAsOverdue(int $invoiceId): Model
    {
        return $this->update($invoiceId, ['status' => 'overdue']);
    }

    /**
     * Cancel invoice
     */
    public function cancel(int $invoiceId, ?string $reason = null): Model
    {
        return $this->update($invoiceId, [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now()
        ]);
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue()
    {
        return $this->repository->getOverdue();
    }

    /**
     * Get invoices by customer
     */
    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Calculate total amount
     */
    public function calculateTotal(int $invoiceId): float
    {
        $invoice = $this->repository->findOrFail($invoiceId);
        $subtotal = $invoice->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $taxAmount = $subtotal * ($invoice->tax_percentage ?? 0) / 100;
        $discountAmount = $subtotal * ($invoice->discount_percentage ?? 0) / 100;

        return $subtotal + $taxAmount - $discountAmount;
    }

    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(int $id): string
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $number = str_pad($id, 5, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$number}";
    }
}
