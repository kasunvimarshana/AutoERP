<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\TotalCalculationService;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Exceptions\InvalidInvoiceStatusException;
use Modules\Sales\Exceptions\InvalidPaymentAmountException;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Repositories\InvoiceRepository;

/**
 * Invoice Service
 *
 * Handles business logic for invoices including lifecycle management,
 * payment recording, and status transitions.
 */
class InvoiceService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private CodeGeneratorService $codeGenerator,
        private TotalCalculationService $totalCalculationService
    ) {}

    /**
     * Create a new invoice with items.
     */
    public function createInvoice(array $data, array $items = []): Invoice
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate invoice code if not provided
            if (empty($data['invoice_code'])) {
                $data['invoice_code'] = $this->generateInvoiceCode();
            }

            // Set default status, dates, and amounts
            $data['status'] = $data['status'] ?? InvoiceStatus::DRAFT;
            $data['invoice_date'] = $data['invoice_date'] ?? now();
            $data['paid_amount'] = $data['paid_amount'] ?? '0.00';

            // Calculate due date if not provided
            if (empty($data['due_date'])) {
                $paymentTermsDays = config('sales.invoice.default_payment_terms_days', 30);
                $data['due_date'] = now()->addDays($paymentTermsDays);
            }

            // Calculate totals if items are provided
            if (! empty($items)) {
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Create invoice
            $invoice = $this->invoiceRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['invoice_id'] = $invoice->id;
                    $invoice->items()->create($item);
                }
                $invoice->load('items');
            }

            return $invoice;
        });
    }

    /**
     * Update invoice and recalculate totals.
     */
    public function updateInvoice(string $id, array $data, ?array $items = null): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        if (! $invoice->canModify()) {
            throw new InvalidInvoiceStatusException(
                "Invoice cannot be modified in {$invoice->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($invoice, $data, $items) {
            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $invoice->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    $item['invoice_id'] = $invoice->id;
                    $invoice->items()->create($item);
                }

                // Recalculate totals
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Update invoice
            return $this->invoiceRepository->update($invoice->id, $data);
        });
    }

    /**
     * Send invoice to customer.
     */
    public function sendInvoice(string $id): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        if (! $invoice->canSend()) {
            throw new InvalidInvoiceStatusException(
                "Invoice cannot be sent in {$invoice->status->value} status"
            );
        }

        return $this->invoiceRepository->update($invoice->id, [
            'status' => InvoiceStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Record a payment for an invoice.
     */
    public function recordPayment(string $id, array $paymentData): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        if (! $invoice->canAcceptPayment()) {
            throw new InvalidInvoiceStatusException(
                "Invoice cannot accept payments in {$invoice->status->value} status"
            );
        }

        $paymentAmount = (string) $paymentData['amount'];

        // Validate payment amount
        if (MathHelper::compare($paymentAmount, '0') <= 0) {
            throw new InvalidPaymentAmountException('Payment amount must be greater than zero');
        }

        $remainingAmount = $invoice->getRemainingAmount();
        if (MathHelper::compare($paymentAmount, $remainingAmount) > 0) {
            throw new InvalidPaymentAmountException(
                "Payment amount exceeds remaining balance of {$remainingAmount}"
            );
        }

        return TransactionHelper::execute(function () use ($invoice, $paymentData, $paymentAmount) {
            // Record payment
            $payment = $invoice->payments()->create([
                'tenant_id' => $invoice->tenant_id,
                'organization_id' => $invoice->organization_id,
                'amount' => $paymentAmount,
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'received_by' => $paymentData['received_by'] ?? null,
            ]);

            // Update invoice paid amount
            $newPaidAmount = MathHelper::add((string) $invoice->paid_amount, $paymentAmount);

            // Determine new status based on payment
            $newStatus = $this->determineStatusAfterPayment(
                $newPaidAmount,
                (string) $invoice->total_amount
            );

            // Update invoice
            $updateData = [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
            ];

            // Set paid_at if fully paid
            if ($newStatus === InvoiceStatus::PAID) {
                $updateData['paid_at'] = now();
            }

            $this->invoiceRepository->update($invoice->id, $updateData);

            return $invoice->fresh(['payments', 'items']);
        });
    }

    /**
     * Cancel invoice.
     */
    public function cancelInvoice(string $id, ?string $reason = null): Invoice
    {
        $invoice = $this->invoiceRepository->findOrFail($id);

        if ($invoice->status === InvoiceStatus::PAID) {
            throw new InvalidInvoiceStatusException('Cannot cancel a paid invoice');
        }

        if ($invoice->isPartiallyPaid()) {
            throw new InvalidInvoiceStatusException(
                'Cannot cancel a partially paid invoice. Please refund payments first.'
            );
        }

        return $this->invoiceRepository->update($invoice->id, [
            'status' => InvoiceStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark overdue invoices.
     */
    public function deleteInvoice(string $id): void
    {
        TransactionHelper::execute(function () use ($id) {
            $this->invoiceRepository->delete($id);
        });
    }

    public function markOverdueInvoices(): int
    {
        $overdueInvoices = Invoice::overdue()->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            // Skip if already marked as overdue
            if ($invoice->status === InvoiceStatus::OVERDUE) {
                continue;
            }

            $this->invoiceRepository->update($invoice->id, [
                'status' => InvoiceStatus::OVERDUE,
                'overdue_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Calculate totals for invoice items.
     */
    private function calculateTotals(array $items, array $data): array
    {
        return $this->totalCalculationService->calculateLineTotals($items, $data);
    }

    /**
     * Determine invoice status after payment.
     */
    private function determineStatusAfterPayment(string $paidAmount, string $totalAmount): InvoiceStatus
    {
        $comparison = MathHelper::compare($paidAmount, $totalAmount);

        if ($comparison >= 0) {
            return InvoiceStatus::PAID;
        }

        if (MathHelper::compare($paidAmount, '0') > 0) {
            return InvoiceStatus::PARTIALLY_PAID;
        }

        return InvoiceStatus::UNPAID;
    }

    /**
     * Generate unique invoice code.
     */
    private function generateInvoiceCode(): string
    {
        $prefix = config('sales.invoice.code_prefix', 'INV-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->invoiceRepository->findByCode($code) !== null
        );
    }
}
