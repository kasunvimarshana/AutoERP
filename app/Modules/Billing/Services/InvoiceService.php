<?php

namespace App\Modules\Billing\Services;

use App\Core\Services\BaseService;
use App\Modules\Billing\Repositories\InvoiceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Service
 * 
 * Handles business logic for invoice operations
 */
class InvoiceService extends BaseService
{
    /**
     * Constructor
     *
     * @param InvoiceRepository $repository
     */
    public function __construct(InvoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new invoice
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
        }

        if (isset($data['items'])) {
            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemTotal * (($item['tax_rate'] ?? 0) / 100);
                $subtotal += $itemTotal;
                $taxAmount += $itemTax;
            }

            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $subtotal + $taxAmount - ($data['discount_amount'] ?? 0);
            $data['paid_amount'] = 0;
            $data['status'] = $data['status'] ?? 'draft';
        }

        return parent::create($data);
    }

    /**
     * Generate unique invoice number
     *
     * @return string
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Y') . '-';
        $lastInvoice = $this->repository->model
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
        
        $number = 1;
        if ($lastInvoice && str_starts_with($lastInvoice->invoice_number, $prefix)) {
            $extracted = substr($lastInvoice->invoice_number, strlen($prefix));
            if (is_numeric($extracted)) {
                $number = (int)$extracted + 1;
            }
        }
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Find invoice by invoice number
     *
     * @param string $invoiceNumber
     * @return mixed
     */
    public function findByInvoiceNumber(string $invoiceNumber)
    {
        return $this->repository->findByInvoiceNumber($invoiceNumber);
    }

    /**
     * Get overdue invoices
     *
     * @return Collection
     */
    public function getOverdueInvoices(): Collection
    {
        return $this->repository->overdueInvoices();
    }

    /**
     * Get invoices by customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function getCustomerInvoices(int $customerId): Collection
    {
        return $this->repository->findByCustomer($customerId);
    }

    /**
     * Get invoices by status
     *
     * @param string $status
     * @return Collection
     */
    public function getInvoicesByStatus(string $status): Collection
    {
        return $this->repository->findByStatus($status);
    }

    /**
     * Get unpaid invoices
     *
     * @return Collection
     */
    public function getUnpaidInvoices(): Collection
    {
        return $this->repository->unpaidInvoices();
    }

    /**
     * Send invoice to customer
     *
     * @param int $invoiceId
     * @param array $data
     * @return bool
     */
    public function sendInvoice(int $invoiceId, array $data): bool
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        $invoice->status = 'sent';
        $invoice->save();

        return true;
    }

    /**
     * Record payment for invoice
     *
     * @param int $invoiceId
     * @param array $data
     * @return mixed
     */
    public function recordPayment(int $invoiceId, array $data)
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            $invoice = $this->find($invoiceId);
            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            $paidAmount = ($invoice->paid_amount ?? 0) + $data['amount'];
            $invoice->paid_amount = $paidAmount;

            if ($paidAmount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partial';
            }

            $invoice->save();

            return [
                'invoice_id' => $invoiceId,
                'payment_amount' => $data['amount'],
                'total_paid' => $paidAmount,
                'remaining_balance' => max(0, $invoice->total_amount - $paidAmount),
                'status' => $invoice->status,
            ];
        });
    }

    /**
     * Generate PDF for invoice
     *
     * @param int $invoiceId
     * @return mixed
     */
    public function generatePdf(int $invoiceId)
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        throw new \Exception('PDF generation not yet implemented. Please install a PDF library like dompdf or barryvdh/laravel-dompdf.');
    }

    /**
     * Search invoices
     *
     * @param string|null $query
     * @return Collection
     */
    public function search(?string $query): Collection
    {
        if (empty($query)) {
            return $this->repository->all();
        }

        return $this->repository->model
            ->where(function ($q) use ($query) {
                $q->where('invoice_number', 'like', "%{$query}%")
                  ->orWhere('notes', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('email', 'like', "%{$query}%")
                                    ->orWhere('company_name', 'like', "%{$query}%")
                                    ->orWhere('first_name', 'like', "%{$query}%")
                                    ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->get();
    }
}
