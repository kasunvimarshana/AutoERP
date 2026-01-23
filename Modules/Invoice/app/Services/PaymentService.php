<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Enums\PaymentStatus;
use Modules\Invoice\Models\Payment;
use Modules\Invoice\Repositories\InvoiceRepository;
use Modules\Invoice\Repositories\PaymentRepository;

/**
 * Payment Service
 *
 * Contains business logic for Payment operations
 */
class PaymentService extends BaseService
{
    /**
     * PaymentService constructor
     */
    public function __construct(
        PaymentRepository $repository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceService $invoiceService
    ) {
        parent::__construct($repository);
    }

    /**
     * Record a new payment
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function recordPayment(array $data): Payment
    {
        DB::beginTransaction();
        try {
            $invoice = $this->invoiceRepository->find($data['invoice_id']);

            if (! $invoice) {
                throw new ServiceException('Invoice not found');
            }

            // Validate payment amount
            if ($data['amount'] <= 0) {
                throw new ServiceException('Payment amount must be greater than zero');
            }

            if ($data['amount'] > $invoice->balance) {
                throw new ServiceException('Payment amount cannot exceed invoice balance');
            }

            // Generate payment number if not provided
            if (! isset($data['payment_number'])) {
                $data['payment_number'] = $this->generateUniquePaymentNumber();
            }

            // Set payment date if not provided
            if (! isset($data['payment_date'])) {
                $data['payment_date'] = now();
            }

            // Default status
            if (! isset($data['status'])) {
                $data['status'] = PaymentStatus::COMPLETED->value;
            }

            // Create payment record
            $payment = parent::create($data);

            // Update invoice amount_paid and balance
            $invoice->amount_paid += $payment->amount;
            $invoice->balance = $invoice->total_amount - $invoice->amount_paid;
            $invoice->save();

            // Update invoice status based on payment
            $this->invoiceService->updateStatusAfterPayment($invoice->id);

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to record payment: '.$e->getMessage());
        }
    }

    /**
     * Void a payment
     *
     * @throws ServiceException
     */
    public function voidPayment(int $id, ?string $notes = null): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->find($id);

            if (! $payment) {
                throw new ServiceException('Payment not found');
            }

            if ($payment->status === PaymentStatus::VOIDED->value) {
                throw new ServiceException('Payment is already voided');
            }

            $invoice = $this->invoiceRepository->find($payment->invoice_id);

            // Update payment status
            $payment->status = PaymentStatus::VOIDED->value;
            if ($notes) {
                $payment->notes = ($payment->notes ? $payment->notes."\n" : '').'Voided: '.$notes;
            }
            $payment->save();

            // Reverse payment from invoice
            $invoice->amount_paid -= $payment->amount;
            $invoice->balance = $invoice->total_amount - $invoice->amount_paid;
            $invoice->save();

            // Update invoice status
            $this->invoiceService->updateStatusAfterPayment($invoice->id);

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to void payment: '.$e->getMessage());
        }
    }

    /**
     * Get payment history
     *
     * @param  array<string, mixed>  $filters
     */
    public function getHistory(array $filters = []): mixed
    {
        return $this->repository->getHistory($filters);
    }

    /**
     * Get payments for invoice
     */
    public function getForInvoice(int $invoiceId): mixed
    {
        return $this->repository->getForInvoice($invoiceId);
    }

    /**
     * Get payment with relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Generate unique payment number
     */
    private function generateUniquePaymentNumber(): string
    {
        do {
            $paymentNumber = Payment::generatePaymentNumber();
        } while ($this->repository->paymentNumberExists($paymentNumber));

        return $paymentNumber;
    }
}
