<?php

namespace App\Modules\InvoicingManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InvoicingManagement\Events\PaymentReceived;
use App\Modules\InvoicingManagement\Repositories\PaymentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    protected InvoiceService $invoiceService;

    public function __construct(PaymentRepository $repository, InvoiceService $invoiceService)
    {
        parent::__construct($repository);
        $this->invoiceService = $invoiceService;
    }

    /**
     * After payment creation hook
     */
    protected function afterCreate(Model $payment, array $data): void
    {
        $payment->payment_reference = $this->generatePaymentReference($payment->id);
        $payment->save();
        
        event(new PaymentReceived($payment));
    }

    /**
     * Apply payment to invoice
     */
    public function applyToInvoice(int $paymentId, int $invoiceId, float $amount): Model
    {
        try {
            DB::beginTransaction();

            $payment = $this->repository->findOrFail($paymentId);
            $invoice = app(\App\Modules\InvoicingManagement\Repositories\InvoiceRepository::class)->findOrFail($invoiceId);

            // Update payment allocation
            $payment->invoice_id = $invoiceId;
            $payment->allocated_amount = $amount;
            $payment->save();

            // Update invoice payment status
            $totalPaid = $invoice->payments->sum('allocated_amount');
            $invoiceTotal = $this->invoiceService->calculateTotal($invoiceId);

            if ($totalPaid >= $invoiceTotal) {
                $this->invoiceService->markAsPaid($invoiceId, [
                    'paid_at' => $payment->payment_date,
                    'payment_method' => $payment->payment_method
                ]);
            }

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get payments by invoice
     */
    public function getByInvoice(int $invoiceId)
    {
        return $this->repository->getByInvoice($invoiceId);
    }

    /**
     * Get payments by customer
     */
    public function getByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    /**
     * Get payments by payment method
     */
    public function getByPaymentMethod(string $paymentMethod)
    {
        return $this->repository->getByPaymentMethod($paymentMethod);
    }

    /**
     * Get payments by date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    /**
     * Void a payment
     */
    public function void(int $paymentId, string $reason): Model
    {
        return $this->update($paymentId, [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => now()
        ]);
    }

    /**
     * Generate payment reference
     */
    protected function generatePaymentReference(int $id): string
    {
        $prefix = 'PAY';
        $date = date('Ymd');
        $number = str_pad($id, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$number}";
    }
}
