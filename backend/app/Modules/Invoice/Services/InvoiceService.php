<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Services;

use App\Core\Services\BaseService;
use App\Modules\Invoice\Repositories\InvoiceRepositoryInterface;
use App\Modules\Invoice\Repositories\InvoicePaymentRepositoryInterface;
use App\Modules\Sales\Repositories\SalesOrderRepositoryInterface;
use App\Modules\CRM\Repositories\CustomerRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceService extends BaseService
{
    protected InvoicePaymentRepositoryInterface $paymentRepository;
    protected SalesOrderRepositoryInterface $salesOrderRepository;
    protected CustomerRepositoryInterface $customerRepository;

    public function __construct(
        InvoiceRepositoryInterface $repository,
        InvoicePaymentRepositoryInterface $paymentRepository,
        SalesOrderRepositoryInterface $salesOrderRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($repository);
        $this->paymentRepository = $paymentRepository;
        $this->salesOrderRepository = $salesOrderRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Create invoice with items.
     */
    public function createInvoiceWithItems(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateInvoiceTotals($items, $data);
            
            $invoiceData = array_merge($data, [
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => $data['status'] ?? 'draft',
                'invoice_date' => $data['invoice_date'] ?? now(),
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'paid_amount' => 0,
                'balance_amount' => $calculations['total_amount'],
                'payment_status' => 'unpaid',
                'created_by' => Auth::id(),
            ]);

            $invoice = $this->repository->create($invoiceData);

            // Create invoice items
            foreach ($items as $item) {
                $itemCalculations = $this->calculateItemTotals($item);
                
                $invoice->items()->create(array_merge($item, [
                    'tenant_id' => $invoice->tenant_id,
                    'discount_amount' => $itemCalculations['discount_amount'],
                    'tax_amount' => $itemCalculations['tax_amount'],
                    'subtotal' => $itemCalculations['subtotal'],
                    'total' => $itemCalculations['total'],
                ]));
            }

            return $invoice->load(['items', 'customer', 'currency', 'payments']);
        });
    }

    /**
     * Create invoice from sales order.
     */
    public function createFromSalesOrder(int $salesOrderId): mixed
    {
        return DB::transaction(function () use ($salesOrderId) {
            $order = $this->salesOrderRepository->findOrFail($salesOrderId);

            if (!$order->isApproved()) {
                throw new ServiceException('Only approved sales orders can be invoiced');
            }

            // Prepare invoice data from order
            $invoiceData = [
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer->name ?? $order->customer_contact_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'customer_tax_number' => $order->customer->tax_number ?? null,
                'organization_id' => $order->organization_id,
                'branch_id' => $order->branch_id,
                'currency_id' => $order->currency_id,
                'exchange_rate' => $order->exchange_rate,
                'subtotal' => $order->subtotal,
                'discount_type' => $order->discount_type,
                'discount_value' => $order->discount_value,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'shipping_amount' => $order->shipping_amount,
                'adjustment_amount' => $order->adjustment_amount,
                'total_amount' => $order->total_amount,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'notes' => $order->notes,
                'terms_and_conditions' => $order->terms_and_conditions,
                'items' => [],
            ];

            // Prepare items
            foreach ($order->items as $orderItem) {
                $invoiceData['items'][] = [
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'variant_id' => $orderItem->variant_id,
                    'item_name' => $orderItem->item_name,
                    'item_description' => $orderItem->item_description,
                    'quantity' => $orderItem->quantity,
                    'unit_id' => $orderItem->unit_id,
                    'unit_price' => $orderItem->unit_price,
                    'discount_type' => $orderItem->discount_type,
                    'discount_value' => $orderItem->discount_value,
                    'discount_amount' => $orderItem->discount_amount,
                    'tax_rate_id' => $orderItem->tax_rate_id,
                    'tax_percentage' => $orderItem->tax_percentage,
                    'tax_amount' => $orderItem->tax_amount,
                    'subtotal' => $orderItem->subtotal,
                    'total' => $orderItem->total,
                ];
            }

            return $this->createInvoiceWithItems($invoiceData);
        });
    }

    /**
     * Update invoice with items.
     */
    public function updateInvoiceWithItems(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = $this->repository->findOrFail($id);

            if (!$invoice->isEditable()) {
                throw new ServiceException('Invoice cannot be edited in current status');
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateInvoiceTotals($items, $data);
            
            $invoiceData = array_merge($data, [
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'balance_amount' => $calculations['total_amount'] - (float)$invoice->paid_amount,
                'updated_by' => Auth::id(),
            ]);

            $invoice = $this->repository->update($id, $invoiceData);

            // Update items
            if (!empty($items)) {
                $invoice->items()->delete();
                
                foreach ($items as $item) {
                    $itemCalculations = $this->calculateItemTotals($item);
                    
                    $invoice->items()->create(array_merge($item, [
                        'tenant_id' => $invoice->tenant_id,
                        'discount_amount' => $itemCalculations['discount_amount'],
                        'tax_amount' => $itemCalculations['tax_amount'],
                        'subtotal' => $itemCalculations['subtotal'],
                        'total' => $itemCalculations['total'],
                    ]));
                }
            }

            return $invoice->load(['items', 'customer', 'currency', 'payments']);
        });
    }

    /**
     * Approve invoice.
     */
    public function approveInvoice(int $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $invoice = $this->repository->findOrFail($id);

            if ($invoice->status !== 'pending') {
                throw new ServiceException('Only pending invoices can be approved');
            }

            return $this->repository->update($id, [
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Record payment for invoice.
     */
    public function recordPayment(int $invoiceId, array $paymentData): mixed
    {
        return DB::transaction(function () use ($invoiceId, $paymentData) {
            $invoice = $this->repository->findOrFail($invoiceId);

            if ($invoice->isPaid()) {
                throw new ServiceException('Invoice is already fully paid');
            }

            $amount = (float) $paymentData['amount'];
            $remainingBalance = (float) $invoice->balance_amount;

            if ($amount > $remainingBalance) {
                throw new ServiceException('Payment amount exceeds remaining balance');
            }

            // Convert payment amount to invoice currency if needed
            $exchangeRate = (float) ($paymentData['exchange_rate'] ?? 1);
            $amountInInvoiceCurrency = $amount * $exchangeRate;

            // Create payment record
            $payment = $this->paymentRepository->create(array_merge($paymentData, [
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoiceId,
                'payment_number' => $this->generatePaymentNumber(),
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'amount_in_invoice_currency' => $amountInInvoiceCurrency,
                'created_by' => Auth::id(),
            ]));

            // Update invoice payment status
            $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($invoiceId);
            $newBalance = (float)$invoice->total_amount - $totalPaid;

            $paymentStatus = 'unpaid';
            if ($newBalance <= 0) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partial';
            }

            $this->repository->update($invoiceId, [
                'paid_amount' => $totalPaid,
                'balance_amount' => $newBalance,
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentStatus === 'paid' ? now() : null,
            ]);

            return $payment;
        });
    }

    /**
     * Calculate item totals.
     */
    protected function calculateItemTotals(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $subtotal = $quantity * $unitPrice;

        $discountAmount = 0;
        if (isset($item['discount_type']) && isset($item['discount_value'])) {
            if ($item['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $item['discount_value'] / 100);
            } elseif ($item['discount_type'] === 'flat') {
                $discountAmount = (float) $item['discount_value'];
            }
        }

        $afterDiscount = $subtotal - $discountAmount;

        $taxAmount = 0;
        if (isset($item['tax_percentage'])) {
            $taxAmount = $afterDiscount * ((float) $item['tax_percentage'] / 100);
        }

        $total = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate invoice totals.
     */
    protected function calculateInvoiceTotals(array $items, array $headerData): array
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $itemCalcs = $this->calculateItemTotals($item);
            $subtotal += $itemCalcs['subtotal'];
            $totalTax += $itemCalcs['tax_amount'];
        }

        $discountAmount = 0;
        if (isset($headerData['discount_type']) && isset($headerData['discount_value'])) {
            if ($headerData['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $headerData['discount_value'] / 100);
            } elseif ($headerData['discount_type'] === 'flat') {
                $discountAmount = (float) $headerData['discount_value'];
            }
        }

        $shippingAmount = (float) ($headerData['shipping_amount'] ?? 0);
        $adjustmentAmount = (float) ($headerData['adjustment_amount'] ?? 0);

        $totalAmount = $subtotal - $discountAmount + $totalTax + $shippingAmount + $adjustmentAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Generate unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = $this->repository->getModel()
            ->where('invoice_number', 'like', $prefix . $date . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique payment number.
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $lastPayment = $this->paymentRepository->getModel()
            ->where('payment_number', 'like', $prefix . $date . '%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
