<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Entities\Invoice;
use Modules\Accounting\Events\InvoiceCreated;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Core\Services\BaseService;

/**
 * Invoice Service
 *
 * Handles business logic for invoice management.
 */
class InvoiceService extends BaseService
{
    /**
     * InvoiceService constructor.
     */
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected InvoiceLineRepositoryInterface $lineRepository,
        protected AccountRepositoryInterface $accountRepository,
        protected JournalEntryService $journalEntryService
    ) {}

    /**
     * Get paginated invoices.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($perPage);
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate invoice number if not provided
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber();
            }

            // Set default values
            $data['status'] = $data['status'] ?? Invoice::STATUS_DRAFT;
            $data['invoice_date'] = $data['invoice_date'] ?? now();
            $data['subtotal'] = 0;
            $data['tax_amount'] = 0;
            $data['discount_amount'] = $data['discount_amount'] ?? 0;
            $data['total_amount'] = 0;
            $data['amount_paid'] = 0;
            $data['amount_due'] = 0;

            // Create invoice
            $invoice = $this->invoiceRepository->create($data);

            // Create invoice lines if provided
            if (! empty($data['lines'])) {
                foreach ($data['lines'] as $index => $lineData) {
                    $lineData['invoice_id'] = $invoice->id;
                    $lineData['sort_order'] = $index + 1;
                    $this->lineRepository->create($lineData);
                }

                // Calculate totals
                $invoice->calculateTotals();
            }

            event(new InvoiceCreated($invoice));

            $this->logInfo('Invoice created', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * Update an existing invoice.
     *
     * @throws \Exception
     */
    public function update(int $id, array $data): Invoice
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $invoice = $this->invoiceRepository->findById($id);

            if (! $invoice) {
                throw new \Exception('Invoice not found');
            }

            if ($invoice->isPaid()) {
                throw new \Exception('Cannot update paid invoice');
            }

            // Update invoice
            $invoice = $this->invoiceRepository->update($id, $data);

            // Update lines if provided
            if (isset($data['lines'])) {
                // Delete existing lines
                foreach ($invoice->lines as $line) {
                    $this->lineRepository->delete($line->id);
                }

                // Create new lines
                foreach ($data['lines'] as $index => $lineData) {
                    $lineData['invoice_id'] = $invoice->id;
                    $lineData['sort_order'] = $index + 1;
                    $this->lineRepository->create($lineData);
                }

                // Calculate totals
                $invoice->calculateTotals();
            }

            $this->logInfo('Invoice updated', [
                'invoice_id' => $invoice->id,
            ]);

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * Delete an invoice.
     *
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw new \Exception('Invoice not found');
        }

        if ($invoice->amount_paid > 0) {
            throw new \Exception('Cannot delete invoice with payments');
        }

        // Delete lines first
        foreach ($invoice->lines as $line) {
            $this->lineRepository->delete($line->id);
        }

        $result = $this->invoiceRepository->delete($id);

        if ($result) {
            $this->logInfo('Invoice deleted', [
                'invoice_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Send an invoice to customer.
     *
     * @throws \Exception
     */
    public function send(int $id): Invoice
    {
        return $this->executeInTransaction(function () use ($id) {
            $invoice = $this->invoiceRepository->findById($id);

            if (! $invoice) {
                throw new \Exception('Invoice not found');
            }

            if ($invoice->status !== Invoice::STATUS_DRAFT) {
                throw new \Exception('Only draft invoices can be sent');
            }

            // Create journal entry for the invoice
            $this->createJournalEntry($invoice);

            // Update status
            $invoice->status = Invoice::STATUS_SENT;
            $invoice->save();

            // TODO: Send email notification

            $this->logInfo('Invoice sent', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice;
        });
    }

    /**
     * Mark invoice as paid.
     *
     * @throws \Exception
     */
    public function markAsPaid(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw new \Exception('Invoice not found');
        }

        $invoice->status = Invoice::STATUS_PAID;
        $invoice->amount_paid = $invoice->total_amount;
        $invoice->amount_due = 0;
        $invoice->save();

        $this->logInfo('Invoice marked as paid', [
            'invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    /**
     * Find invoice by ID.
     */
    public function findById(int $id): ?Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    /**
     * Get invoices by customer.
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->invoiceRepository->getByCustomer($customerId);
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdueInvoices(): Collection
    {
        return $this->invoiceRepository->getOverdueInvoices();
    }

    /**
     * Get aging report.
     */
    public function getAgingReport(): Collection
    {
        return $this->invoiceRepository->getAgingReport();
    }

    /**
     * Create journal entry for invoice.
     *
     * @throws \Exception
     */
    protected function createJournalEntry(Invoice $invoice): void
    {
        // Get accounts receivable account
        $arAccount = $this->accountRepository
            ->getModel()
            ->where('sub_type', 'accounts_receivable')
            ->first();

        if (! $arAccount) {
            throw new \Exception('Accounts receivable account not found');
        }

        $lines = [];

        // Debit: Accounts Receivable
        $lines[] = [
            'account_id' => $arAccount->id,
            'description' => 'Invoice '.$invoice->invoice_number,
            'debit_amount' => $invoice->total_amount,
            'credit_amount' => 0,
            'currency' => $invoice->currency,
        ];

        // Credit: Revenue accounts from invoice lines
        foreach ($invoice->lines as $line) {
            if ($line->account_id) {
                $lines[] = [
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit_amount' => 0,
                    'credit_amount' => $line->total,
                    'currency' => $invoice->currency,
                ];
            }
        }

        // Create journal entry
        $entryData = [
            'entry_date' => $invoice->invoice_date,
            'reference' => $invoice->invoice_number,
            'description' => 'Customer Invoice - '.$invoice->invoice_number,
            'currency' => $invoice->currency,
            'lines' => $lines,
        ];

        $journalEntry = $this->journalEntryService->create($entryData);
        $this->journalEntryService->post($journalEntry->id);

        // Link journal entry to invoice
        $invoice->journal_entry_id = $journalEntry->id;
        $invoice->save();
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        // Get the last invoice number for this month
        $lastInvoice = $this->invoiceRepository
            ->getModel()
            ->where('invoice_number', 'LIKE', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }
}
