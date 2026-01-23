<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Repositories\InvoiceItemRepository;
use Modules\Invoice\Repositories\InvoiceRepository;
use Modules\JobCard\Repositories\JobCardRepository;

/**
 * Invoice Service
 *
 * Contains business logic for Invoice operations
 */
class InvoiceService extends BaseService
{
    /**
     * InvoiceService constructor
     */
    public function __construct(
        InvoiceRepository $repository,
        private readonly InvoiceItemRepository $itemRepository,
        private readonly JobCardRepository $jobCardRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Create a new invoice
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            if (! isset($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateUniqueInvoiceNumber();
            }

            if (! isset($data['invoice_date'])) {
                $data['invoice_date'] = now();
            }

            if (! isset($data['status'])) {
                $data['status'] = 'draft';
            }

            // Calculate totals if items provided
            if (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $invoice = parent::create($data);

                // Add items and recalculate
                $this->addItems($invoice->id, $items);
                $invoice = $this->recalculateTotals($invoice->id);
            } else {
                // Initialize balance to total
                if (isset($data['total_amount'])) {
                    $data['balance'] = $data['total_amount'];
                }

                $invoice = parent::create($data);
            }

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $invoice;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to create invoice: '.$e->getMessage());
        }
    }

    /**
     * Update invoice
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): mixed
    {
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $invoice = parent::update($id, $data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $invoice;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to update invoice: '.$e->getMessage());
        }
    }

    /**
     * Generate invoice from job card
     *
     * @throws ServiceException
     */
    public function generateFromJobCard(int $jobCardId, array $additionalData = []): Invoice
    {
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $jobCard = $this->jobCardRepository->findWithRelations($jobCardId);

            if (! $jobCard) {
                throw new ServiceException('Job card not found');
            }

            if ($jobCard->status !== 'completed') {
                throw new ServiceException('Job card must be completed before generating invoice');
            }

            $invoiceData = [
                'job_card_id' => $jobCard->id,
                'customer_id' => $jobCard->customer_id,
                'vehicle_id' => $jobCard->vehicle_id,
                'branch_id' => $jobCard->branch_id,
                'invoice_number' => $this->generateUniqueInvoiceNumber(),
                'invoice_date' => now(),
                'status' => 'pending',
                ...$additionalData,
            ];

            $invoice = parent::create($invoiceData);

            // Add items from job card
            $items = [];

            // Add labor from tasks
            foreach ($jobCard->tasks as $task) {
                $items[] = [
                    'item_type' => 'labor',
                    'description' => $task->name.': '.$task->description,
                    'quantity' => $task->hours ?? 1,
                    'unit_price' => $task->labor_rate ?? 0,
                    'total_price' => ($task->hours ?? 1) * ($task->labor_rate ?? 0),
                ];
            }

            // Add parts
            foreach ($jobCard->parts as $part) {
                $items[] = [
                    'item_type' => 'part',
                    'description' => $part->inventoryItem?->name ?? 'Part',
                    'quantity' => $part->quantity,
                    'unit_price' => $part->unit_price,
                    'total_price' => $part->quantity * $part->unit_price,
                ];
            }

            if (! empty($items)) {
                $this->addItems($invoice->id, $items);
                $invoice = $this->recalculateTotals($invoice->id);
            }

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $invoice;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to generate invoice from job card: '.$e->getMessage());
        }
    }

    /**
     * Add items to invoice
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function addItems(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            $item['invoice_id'] = $invoiceId;

            // Calculate total price if not provided
            if (! isset($item['total_price'])) {
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
            }

            $this->itemRepository->create($item);
        }
    }

    /**
     * Recalculate invoice totals
     */
    public function recalculateTotals(int $invoiceId): Invoice
    {
        $invoice = $this->repository->find($invoiceId);
        $items = $this->itemRepository->getForInvoice($invoiceId);

        $subtotal = $items->sum('total_price');
        $taxRate = $invoice->tax_rate ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = $invoice->discount_amount ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        $balance = $totalAmount - $invoice->amount_paid;

        $invoice->subtotal = $subtotal;
        $invoice->tax_amount = $taxAmount;
        $invoice->total_amount = $totalAmount;
        $invoice->balance = $balance;
        $invoice->save();

        return $invoice;
    }

    /**
     * Update invoice status based on payment
     */
    public function updateStatusAfterPayment(int $invoiceId): Invoice
    {
        $invoice = $this->repository->find($invoiceId);

        if ($invoice->balance <= 0) {
            $invoice->status = 'paid';
        } elseif ($invoice->amount_paid > 0 && $invoice->balance > 0) {
            $invoice->status = 'partial';
        }

        $invoice->save();

        return $invoice;
    }

    /**
     * Get invoice with all relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Get outstanding invoices
     */
    public function getOutstanding(): mixed
    {
        return $this->repository->getOutstanding();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): mixed
    {
        return $this->repository->getOverdue();
    }

    /**
     * Search invoices with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): mixed
    {
        return $this->repository->getWithFilters($filters);
    }

    /**
     * Generate unique invoice number
     */
    private function generateUniqueInvoiceNumber(): string
    {
        do {
            $invoiceNumber = Invoice::generateInvoiceNumber();
        } while ($this->repository->invoiceNumberExists($invoiceNumber));

        return $invoiceNumber;
    }
}
