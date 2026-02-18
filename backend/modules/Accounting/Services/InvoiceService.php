<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\InvoiceStatus;
use Modules\Accounting\Events\InvoiceGenerated;
use Modules\Accounting\Events\InvoicePaid;
use Modules\Accounting\Events\InvoiceSent;
use Modules\Accounting\Models\Invoice;
use Modules\Accounting\Repositories\InvoiceRepository;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;

/**
 * Invoice Service
 *
 * Handles all business logic for invoice management.
 */
class InvoiceService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected InvoiceRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all invoices with optional filters.
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

        if (isset($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('due_date', '<', now())
                ->whereIn('status', ['sent', 'partial', 'overdue']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', "%{$filters['search']}%");
            });
        }

        $query->with(['customer', 'items.product', 'salesOrder']);
        $query->orderBy('invoice_date', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Generate invoice number if not provided
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber();
            }

            // Calculate totals
            $this->calculateTotals($data);

            // Set defaults
            $data['status'] = $data['status'] ?? InvoiceStatus::DRAFT;
            $data['paid_amount'] = $data['paid_amount'] ?? 0;
            $data['balance_due'] = $data['total_amount'] - $data['paid_amount'];
            $data['currency_code'] = $data['currency_code'] ?? config('app.default_currency', 'USD');

            // Create invoice
            $invoice = $this->repository->create($data);

            // Create invoice items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $itemData['invoice_id'] = $invoice->id;
                    $invoice->items()->create($itemData);
                }
            }

            return $invoice->load(['items.product', 'customer', 'salesOrder']);
        });
    }

    /**
     * Generate invoice from sales order.
     */
    public function generateFromSalesOrder(string $orderId): Invoice
    {
        return DB::transaction(function () use ($orderId) {
            $order = \Modules\Sales\Models\SalesOrder::with(['items.product', 'customer'])->findOrFail($orderId);

            // Create invoice data from order
            $paymentTerms = config('accounting.default_payment_terms', 30);
            $invoiceData = [
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays($paymentTerms),
                'billing_address' => $order->billing_address,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'notes' => $order->notes,
                'items' => [],
            ];

            // Convert order items to invoice items
            foreach ($order->items as $orderItem) {
                $invoiceData['items'][] = [
                    'product_id' => $orderItem->product_id,
                    'description' => $orderItem->product->name ?? $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'tax_rate' => $orderItem->tax_rate ?? 0,
                    'tax_amount' => $orderItem->tax_amount ?? 0,
                    'discount_amount' => $orderItem->discount_amount ?? 0,
                    'total_amount' => $orderItem->total_amount,
                ];
            }

            $invoice = $this->create($invoiceData);

            event(new InvoiceGenerated($invoice));

            return $invoice;
        });
    }

    /**
     * Update an existing invoice.
     */
    public function update(string $id, array $data): Invoice
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = $this->repository->findOrFail($id);

            // Can only edit draft invoices
            if (! $invoice->status->canEdit() && isset($data['items'])) {
                throw new \Exception('Cannot edit items of non-draft invoice.');
            }

            // Calculate totals if items changed
            if (isset($data['items'])) {
                $this->calculateTotals($data);

                // Update balance due
                $data['balance_due'] = $data['total_amount'] - $invoice->paid_amount;

                // Update items
                $invoice->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $itemData['invoice_id'] = $invoice->id;
                    $invoice->items()->create($itemData);
                }
            }

            $invoice->update($data);

            return $invoice->load(['items.product', 'customer', 'salesOrder']);
        });
    }

    /**
     * Send invoice to customer.
     */
    public function sendInvoice(string $id): Invoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = $this->repository->findOrFail($id);

            if ($invoice->status !== InvoiceStatus::DRAFT) {
                throw new \Exception('Only draft invoices can be sent.');
            }

            $invoice->status = InvoiceStatus::SENT;
            $invoice->sent_at = now();
            $invoice->save();

            event(new InvoiceSent($invoice));

            return $invoice->load(['items.product', 'customer']);
        });
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(string $id): Invoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = $this->repository->findOrFail($id);

            $invoice->status = InvoiceStatus::PAID;
            $invoice->paid_amount = $invoice->total_amount;
            $invoice->balance_due = 0;
            $invoice->paid_at = now();
            $invoice->save();

            event(new InvoicePaid($invoice));

            return $invoice->load(['items.product', 'customer']);
        });
    }

    /**
     * Update invoice payment status based on paid amount.
     */
    public function updatePaymentStatus(string $id, float $paidAmount): Invoice
    {
        return DB::transaction(function () use ($id, $paidAmount) {
            $invoice = $this->repository->findOrFail($id);

            $invoice->paid_amount = $paidAmount;
            $invoice->balance_due = $invoice->total_amount - $paidAmount;

            if ($paidAmount >= $invoice->total_amount) {
                $invoice->status = InvoiceStatus::PAID;
                $invoice->paid_at = now();
                event(new InvoicePaid($invoice));
            } elseif ($paidAmount > 0) {
                $invoice->status = InvoiceStatus::PARTIAL;
            } elseif ($invoice->due_date < now()) {
                $invoice->status = InvoiceStatus::OVERDUE;
            }

            $invoice->save();

            return $invoice->load(['items.product', 'customer']);
        });
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = config('accounting.invoice_prefix', 'INV');
        $year = date('Y');

        return DB::transaction(function () use ($prefix, $year) {
            $lastInvoice = $this->repository->query()
                ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
                ->orderBy('invoice_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastInvoice && preg_match('/-(\d+)$/', $lastInvoice->invoice_number, $matches)) {
                $newNumber = (int) $matches[1] + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix.'-'.$year.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Calculate invoice totals.
     */
    protected function calculateTotals(array &$data): void
    {
        $subtotal = 0;
        $taxAmount = 0;

        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemTax = ($itemSubtotal - $itemDiscount) * ($item['tax_rate'] ?? 0) / 100;

                $data['items'][$key]['tax_amount'] = $itemTax;
                $data['items'][$key]['total_amount'] = $itemSubtotal - $itemDiscount + $itemTax;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;
            }
        }

        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['discount_amount'] = $data['discount_amount'] ?? 0;
        $data['total_amount'] = $subtotal + $taxAmount - $data['discount_amount'];
    }
}
