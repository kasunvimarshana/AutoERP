<?php

namespace App\Modules\POS\Services;

use App\Modules\POS\DTOs\InvoiceDTO;
use App\Modules\POS\Enums\InvoiceStatus;
use App\Modules\POS\Events\InvoiceCreated;
use App\Modules\POS\Models\Invoice;
use App\Modules\POS\Models\InvoiceItem;
use App\Modules\POS\Repositories\InvoiceRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService extends BaseService
{
    public function __construct(
        protected InvoiceRepository $repository
    ) {}

    public function create(InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $invoice = $this->repository->create([
                ...$dto->toArray(),
                'tenant_id' => auth()->user()->tenant_id,
                'invoice_number' => $this->repository->generateInvoiceNumber(),
                'status' => InvoiceStatus::PENDING,
            ]);

            $this->createItems($invoice, $dto->items);
            $invoice->calculateTotals();
            $invoice->save();

            // Dispatch event for async processing
            event(new InvoiceCreated($invoice));

            return $invoice->load(['items.product', 'customer']);
        });
    }

    public function update(int $id, InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($id, $dto) {
            $invoice = $this->repository->findOrFail($id);

            if ($invoice->status->isFinal()) {
                throw new \Exception('Cannot edit finalized invoice');
            }

            $invoice->update($dto->toArray());

            // Delete existing items and recreate
            $invoice->items()->delete();
            $this->createItems($invoice, $dto->items);
            $invoice->calculateTotals();
            $invoice->save();

            return $invoice->load(['items.product', 'customer']);
        });
    }

    public function createFromSalesOrder(int $salesOrderId): Invoice
    {
        return DB::transaction(function () use ($salesOrderId) {
            $salesOrder = \App\Modules\POS\Models\SalesOrder::findOrFail($salesOrderId);

            // Check if invoice already exists
            if ($salesOrder->invoice) {
                throw new \Exception('Invoice already exists for this sales order');
            }

            $invoice = $this->repository->create([
                'tenant_id' => $salesOrder->tenant_id,
                'invoice_number' => $this->repository->generateInvoiceNumber(),
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'status' => InvoiceStatus::PENDING,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => $salesOrder->notes,
                'terms_and_conditions' => $salesOrder->terms_and_conditions,
            ]);

            // Copy items from sales order
            foreach ($salesOrder->items as $soItem) {
                $item = new InvoiceItem([
                    'product_id' => $soItem->product_id,
                    'line_number' => $soItem->line_number,
                    'quantity' => $soItem->quantity,
                    'unit' => $soItem->unit,
                    'unit_price' => $soItem->unit_price,
                    'discount_percentage' => $soItem->discount_percentage,
                    'discount_amount' => $soItem->discount_amount,
                    'tax_percentage' => $soItem->tax_percentage,
                    'tax_amount' => $soItem->tax_amount,
                    'line_total' => $soItem->line_total,
                    'description' => $soItem->description,
                ]);
                $invoice->items()->save($item);
            }

            $invoice->calculateTotals();
            $invoice->save();

            // Update sales order status
            $salesOrder->update(['status' => \App\Modules\POS\Enums\SalesOrderStatus::INVOICED]);

            // Dispatch event for async processing
            event(new InvoiceCreated($invoice));

            return $invoice->load(['items.product', 'customer']);
        });
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        return $this->repository->findByCustomer($customerId, $filters);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->repository->findByStatus($status);
    }

    public function findOverdueInvoices(): Collection
    {
        return $this->repository->findOverdueInvoices();
    }

    public function updatePaymentStatus(int $id): Invoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = $this->repository->findOrFail($id);
            $invoice->calculateTotals();
            $invoice->updateStatus();
            $invoice->save();

            return $invoice->load(['items.product', 'customer', 'payments']);
        });
    }

    private function createItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $item = new InvoiceItem([
                'product_id' => $itemData['product_id'],
                'line_number' => $index + 1,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'] ?? 'pcs',
                'unit_price' => $itemData['unit_price'],
                'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                'description' => $itemData['description'] ?? null,
            ]);

            $item->calculateLineTotal();
            $invoice->items()->save($item);
        }
    }
}
