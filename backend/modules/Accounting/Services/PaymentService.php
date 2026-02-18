<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\PaymentStatus;
use Modules\Accounting\Events\PaymentAllocated;
use Modules\Accounting\Events\PaymentReceived;
use Modules\Accounting\Models\Payment;
use Modules\Accounting\Repositories\InvoiceRepository;
use Modules\Accounting\Repositories\PaymentRepository;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;

/**
 * Payment Service
 *
 * Handles all business logic for payment management.
 */
class PaymentService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected PaymentRepository $repository,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceService $invoiceService
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all payments with optional filters.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->repository->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('payment_number', 'like', "%{$filters['search']}%")
                    ->orWhere('reference', 'like', "%{$filters['search']}%");
            });
        }

        $query->with(['customer', 'allocations.invoice']);
        $query->orderBy('payment_date', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new payment.
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Generate payment number if not provided
            if (empty($data['payment_number'])) {
                $data['payment_number'] = $this->generatePaymentNumber();
            }

            // Set defaults
            $data['status'] = $data['status'] ?? PaymentStatus::PENDING;
            $data['currency_code'] = $data['currency_code'] ?? config('app.default_currency', 'USD');

            // Create payment
            $payment = $this->repository->create($data);

            // Handle allocations if provided
            if (isset($data['allocations']) && is_array($data['allocations'])) {
                $this->allocatePayment($payment, $data['allocations']);
            }

            event(new PaymentReceived($payment));

            return $payment->load(['customer', 'allocations.invoice']);
        });
    }

    /**
     * Update an existing payment.
     */
    public function update(string $id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {
            $payment = $this->repository->findOrFail($id);

            // Cannot edit completed payments
            if ($payment->status->isFinalized() && isset($data['amount'])) {
                throw new \Exception('Cannot edit amount of finalized payment.');
            }

            $payment->update($data);

            return $payment->load(['customer', 'allocations.invoice']);
        });
    }

    /**
     * Allocate payment to invoices.
     */
    public function allocatePayment(Payment $payment, array $allocations): Payment
    {
        return DB::transaction(function () use ($payment, $allocations) {
            $totalAllocated = 0;

            foreach ($allocations as $allocation) {
                $invoice = $this->invoiceRepository->findOrFail($allocation['invoice_id']);

                // Validate invoice can receive payment
                if (! $invoice->status->canReceivePayment()) {
                    throw new \Exception("Invoice {$invoice->invoice_number} cannot receive payment.");
                }

                $allocationAmount = $allocation['amount'];

                // Validate allocation amount
                if ($allocationAmount > $invoice->balance_due) {
                    throw new \Exception('Allocation amount exceeds invoice balance due.');
                }

                // Create allocation
                $payment->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                    'notes' => $allocation['notes'] ?? null,
                ]);

                // Update invoice paid amount
                $newPaidAmount = $invoice->paid_amount + $allocationAmount;
                $this->invoiceService->updatePaymentStatus($invoice->id, $newPaidAmount);

                $totalAllocated += $allocationAmount;

                event(new PaymentAllocated($payment, $invoice, $allocationAmount));
            }

            // Validate total allocated doesn't exceed payment amount
            if ($totalAllocated > $payment->amount) {
                throw new \Exception('Total allocated amount exceeds payment amount.');
            }

            // Mark payment as completed if fully allocated
            if ($totalAllocated >= $payment->amount) {
                $payment->status = PaymentStatus::COMPLETED;
                $payment->save();
            }

            return $payment->load(['customer', 'allocations.invoice']);
        });
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(string $id): Payment
    {
        return DB::transaction(function () use ($id) {
            $payment = $this->repository->findOrFail($id);

            if (! $payment->status->canCancel()) {
                throw new \Exception('Payment cannot be marked as completed.');
            }

            $payment->status = PaymentStatus::COMPLETED;
            $payment->save();

            return $payment->load(['customer', 'allocations.invoice']);
        });
    }

    /**
     * Cancel a payment.
     */
    public function cancel(string $id): Payment
    {
        return DB::transaction(function () use ($id) {
            $payment = $this->repository->findOrFail($id);

            if (! $payment->status->canCancel()) {
                throw new \Exception('Payment cannot be cancelled.');
            }

            // Remove allocations and update invoices
            foreach ($payment->allocations as $allocation) {
                $invoice = $allocation->invoice;
                $newPaidAmount = $invoice->paid_amount - $allocation->amount;
                $this->invoiceService->updatePaymentStatus($invoice->id, $newPaidAmount);
                $allocation->delete();
            }

            $payment->status = PaymentStatus::CANCELLED;
            $payment->save();

            return $payment->load(['customer', 'allocations.invoice']);
        });
    }

    /**
     * Generate a unique payment number.
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = config('accounting.payment_prefix', 'PAY');
        $year = date('Y');

        return DB::transaction(function () use ($prefix, $year) {
            $lastPayment = $this->repository->query()
                ->where('payment_number', 'like', "{$prefix}-{$year}-%")
                ->orderBy('payment_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastPayment && preg_match('/-(\d+)$/', $lastPayment->payment_number, $matches)) {
                $newNumber = (int) $matches[1] + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix.'-'.$year.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
