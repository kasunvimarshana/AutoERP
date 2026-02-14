<?php

namespace App\Modules\Billing\Services;

use App\Core\Services\BaseService;
use App\Modules\Billing\Repositories\InvoiceRepository;
use App\Modules\Billing\Repositories\PaymentRepository;
use App\Modules\Customer\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService extends BaseService
{
    protected InvoiceRepository $invoiceRepository;

    protected CustomerService $customerService;

    /**
     * PaymentService constructor
     */
    public function __construct(
        PaymentRepository $repository,
        InvoiceRepository $invoiceRepository,
        CustomerService $customerService
    ) {
        $this->repository = $repository;
        $this->invoiceRepository = $invoiceRepository;
        $this->customerService = $customerService;
    }

    /**
     * Record payment
     */
    public function recordPayment(array $data): mixed
    {
        DB::beginTransaction();

        try {
            $invoice = $this->invoiceRepository->findOrFail($data['invoice_id']);

            $paymentData = [
                'invoice_id' => $data['invoice_id'],
                'customer_id' => $invoice->customer_id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'] ?? now(),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ];

            $payment = $this->repository->create($paymentData);

            $totalPaid = $this->repository->getTotalByInvoice($data['invoice_id']);

            if ($totalPaid >= $invoice->total_amount) {
                $this->invoiceRepository->update($data['invoice_id'], [
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            } elseif ($totalPaid > 0) {
                $this->invoiceRepository->update($data['invoice_id'], [
                    'status' => 'partial',
                ]);
            }

            if (isset($data['update_customer_balance']) && $data['update_customer_balance']) {
                $this->customerService->updateBalance($invoice->customer_id, $data['amount'], 'subtract');
            }

            DB::commit();

            Log::info("Payment {$payment->payment_number} recorded for invoice {$data['invoice_id']}");

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording payment: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique payment number
     */
    private function generatePaymentNumber(): string
    {
        return 'PAY-'.date('Ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get payments by invoice
     */
    public function getByInvoice(int $invoiceId)
    {
        try {
            return $this->repository->getByInvoice($invoiceId);
        } catch (\Exception $e) {
            Log::error("Error fetching payments for invoice {$invoiceId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get payments by customer
     */
    public function getByCustomer(int $customerId)
    {
        try {
            return $this->repository->getByCustomer($customerId);
        } catch (\Exception $e) {
            Log::error("Error fetching payments for customer {$customerId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get payments by date range
     */
    public function getByDateRange(string $startDate, string $endDate)
    {
        try {
            return $this->repository->getByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error fetching payments by date range: '.$e->getMessage());
            throw $e;
        }
    }
}
