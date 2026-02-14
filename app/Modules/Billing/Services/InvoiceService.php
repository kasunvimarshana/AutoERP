<?php

namespace App\Modules\Billing\Services;

use App\Core\Services\BaseService;
use App\Modules\Billing\Repositories\InvoiceRepository;
use App\Modules\Billing\Repositories\PaymentRepository;
use App\Modules\Customer\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService extends BaseService
{
    protected PaymentRepository $paymentRepository;

    protected CustomerService $customerService;

    /**
     * InvoiceService constructor
     */
    public function __construct(
        InvoiceRepository $repository,
        PaymentRepository $paymentRepository,
        CustomerService $customerService
    ) {
        $this->repository = $repository;
        $this->paymentRepository = $paymentRepository;
        $this->customerService = $customerService;
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(int $invoiceId, array $paymentData = []): bool
    {
        DB::beginTransaction();

        try {
            $invoice = $this->repository->findOrFail($invoiceId);

            if ($invoice->status === 'paid') {
                throw new \Exception('Invoice is already marked as paid');
            }

            $result = $this->repository->update($invoiceId, [
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if (! empty($paymentData)) {
                $paymentData['invoice_id'] = $invoiceId;
                $paymentData['customer_id'] = $invoice->customer_id;
                $paymentData['amount'] = $paymentData['amount'] ?? $invoice->total_amount;
                $paymentData['payment_date'] = $paymentData['payment_date'] ?? now();
                $paymentData['status'] = 'completed';

                $this->paymentRepository->create($paymentData);
            }

            DB::commit();

            Log::info("Invoice {$invoiceId} marked as paid");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking invoice as paid: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Send invoice to customer
     */
    public function sendToCustomer(int $invoiceId, ?string $email = null): bool
    {
        try {
            $invoice = $this->repository->findOrFail($invoiceId);

            $recipientEmail = $email ?? $invoice->customer->email ?? null;

            if (! $recipientEmail) {
                throw new \Exception('Customer email not found');
            }

            $this->repository->update($invoiceId, [
                'sent_at' => now(),
                'sent_to' => $recipientEmail,
            ]);

            Log::info("Invoice {$invoiceId} sent to {$recipientEmail}");

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending invoice: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue()
    {
        try {
            return $this->repository->getOverdue();
        } catch (\Exception $e) {
            Log::error('Error fetching overdue invoices: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoices by customer
     */
    public function getByCustomer(int $customerId)
    {
        try {
            return $this->repository->getByCustomer($customerId);
        } catch (\Exception $e) {
            Log::error("Error fetching invoices for customer {$customerId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status)
    {
        try {
            return $this->repository->getByStatus($status);
        } catch (\Exception $e) {
            Log::error("Error fetching invoices by status {$status}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoices by date range
     */
    public function getByDateRange(string $startDate, string $endDate)
    {
        try {
            return $this->repository->getByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices by date range: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoice summary
     */
    public function getSummary(?int $customerId = null): array
    {
        try {
            $total = $this->repository->count();
            $pending = $this->repository->getByStatus('pending')->count();
            $paid = $this->repository->getByStatus('paid')->count();
            $overdue = $this->repository->getOverdue()->count();
            $outstanding = $this->repository->getTotalOutstanding($customerId);

            return [
                'total_invoices' => $total,
                'pending_invoices' => $pending,
                'paid_invoices' => $paid,
                'overdue_invoices' => $overdue,
                'total_outstanding' => $outstanding,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching invoice summary: '.$e->getMessage());
            throw $e;
        }
    }
}
