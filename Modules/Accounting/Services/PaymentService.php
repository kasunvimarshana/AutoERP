<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Entities\Payment;
use Modules\Accounting\Events\PaymentReceived;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Core\Services\BaseService;

/**
 * Payment Service
 *
 * Handles business logic for payment processing and reconciliation.
 */
class PaymentService extends BaseService
{
    /**
     * PaymentService constructor.
     */
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected AccountRepositoryInterface $accountRepository,
        protected JournalEntryService $journalEntryService
    ) {}

    /**
     * Get paginated payments.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($perPage);
    }

    /**
     * Create a new payment.
     *
     * @throws \Exception
     */
    public function create(array $data): Payment
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate payment number if not provided
            if (empty($data['payment_number'])) {
                $data['payment_number'] = $this->generatePaymentNumber();
            }

            // Set default values
            $data['status'] = $data['status'] ?? Payment::STATUS_PENDING;
            $data['payment_date'] = $data['payment_date'] ?? now();

            // Validate invoice if provided
            if (! empty($data['invoice_id'])) {
                $invoice = $this->invoiceRepository->findById($data['invoice_id']);
                if (! $invoice) {
                    throw new \Exception('Invoice not found');
                }

                // Set customer from invoice if not provided
                if (empty($data['customer_id'])) {
                    $data['customer_id'] = $invoice->customer_id;
                }

                // Validate payment amount
                if ($data['amount'] > $invoice->amount_due) {
                    throw new \Exception('Payment amount exceeds invoice balance');
                }
            }

            // Create payment
            $payment = $this->paymentRepository->create($data);

            $this->logInfo('Payment created', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);

            return $payment;
        });
    }

    /**
     * Update an existing payment.
     *
     * @throws \Exception
     */
    public function update(int $id, array $data): Payment
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $payment = $this->paymentRepository->findById($id);

            if (! $payment) {
                throw new \Exception('Payment not found');
            }

            if ($payment->isCompleted()) {
                throw new \Exception('Cannot update completed payment');
            }

            $payment = $this->paymentRepository->update($id, $data);

            $this->logInfo('Payment updated', [
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }

    /**
     * Delete a payment.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $payment = $this->paymentRepository->findById($id);

        if (! $payment) {
            throw new \Exception('Payment not found');
        }

        if ($payment->isCompleted()) {
            throw new \Exception('Cannot delete completed payment');
        }

        $result = $this->paymentRepository->delete($id);

        if ($result) {
            $this->logInfo('Payment deleted', [
                'payment_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Apply payment to invoice.
     *
     * @throws \Exception
     */
    public function applyToInvoice(int $paymentId, int $invoiceId, ?float $amount = null): Payment
    {
        return $this->executeInTransaction(function () use ($paymentId, $invoiceId, $amount) {
            $payment = $this->paymentRepository->findById($paymentId);
            if (! $payment) {
                throw new \Exception('Payment not found');
            }

            $invoice = $this->invoiceRepository->findById($invoiceId);
            if (! $invoice) {
                throw new \Exception('Invoice not found');
            }

            // Use payment amount if not specified
            $applyAmount = $amount ?? $payment->amount;

            // Validate amount
            if ($applyAmount > $invoice->amount_due) {
                throw new \Exception('Payment amount exceeds invoice balance');
            }

            // Apply payment to invoice
            $invoice->applyPayment($applyAmount);

            // Update payment
            $payment->invoice_id = $invoiceId;
            $payment->status = Payment::STATUS_COMPLETED;
            $payment->save();

            // Create journal entry
            $this->createJournalEntry($payment);

            event(new PaymentReceived($payment));

            $this->logInfo('Payment applied to invoice', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $applyAmount,
            ]);

            return $payment;
        });
    }

    /**
     * Process payment.
     *
     * @throws \Exception
     */
    public function process(int $id): Payment
    {
        return $this->executeInTransaction(function () use ($id) {
            $payment = $this->paymentRepository->findById($id);

            if (! $payment) {
                throw new \Exception('Payment not found');
            }

            if ($payment->isCompleted()) {
                throw new \Exception('Payment already processed');
            }

            // If linked to invoice, apply payment
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                $invoice->applyPayment($payment->amount);
            }

            // Create journal entry
            $this->createJournalEntry($payment);

            // Update status
            $payment->status = Payment::STATUS_COMPLETED;
            $payment->save();

            event(new PaymentReceived($payment));

            $this->logInfo('Payment processed', [
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }

    /**
     * Find payment by ID.
     */
    public function findById(int $id): ?Payment
    {
        return $this->paymentRepository->findById($id);
    }

    /**
     * Get payments by customer.
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->paymentRepository->getByCustomer($customerId);
    }

    /**
     * Get payments by invoice.
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->paymentRepository->getByInvoice($invoiceId);
    }

    /**
     * Create journal entry for payment.
     *
     * @throws \Exception
     */
    protected function createJournalEntry(Payment $payment): void
    {
        // Get bank account
        $bankAccount = $payment->bank_account_id
            ? $this->accountRepository->findById($payment->bank_account_id)
            : $this->accountRepository->getModel()->where('sub_type', 'cash')->first();

        if (! $bankAccount) {
            throw new \Exception('Bank account not found');
        }

        // Get accounts receivable account
        $arAccount = $this->accountRepository
            ->getModel()
            ->where('sub_type', 'accounts_receivable')
            ->first();

        if (! $arAccount) {
            throw new \Exception('Accounts receivable account not found');
        }

        $lines = [
            // Debit: Bank/Cash Account
            [
                'account_id' => $bankAccount->id,
                'description' => 'Payment received - '.$payment->payment_number,
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
                'currency' => $payment->currency,
            ],
            // Credit: Accounts Receivable
            [
                'account_id' => $arAccount->id,
                'description' => 'Payment received - '.$payment->payment_number,
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
        ];

        // Create journal entry
        $entryData = [
            'entry_date' => $payment->payment_date,
            'reference' => $payment->payment_number,
            'description' => 'Customer Payment - '.$payment->payment_number,
            'currency' => $payment->currency,
            'lines' => $lines,
        ];

        $journalEntry = $this->journalEntryService->create($entryData);
        $this->journalEntryService->post($journalEntry->id);

        // Link journal entry to payment
        $payment->journal_entry_id = $journalEntry->id;
        $payment->save();
    }

    /**
     * Generate a unique payment number.
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');

        // Get the last payment number for this month
        $lastPayment = $this->paymentRepository
            ->getModel()
            ->where('payment_number', 'LIKE', "{$prefix}-{$year}{$month}-%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastPayment->payment_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }
}
