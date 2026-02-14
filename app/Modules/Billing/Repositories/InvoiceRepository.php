<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

/**
 * Invoice Repository
 * 
 * Handles data access operations for invoices
 */
class InvoiceRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Invoice::class;
    }

    /**
     * Find invoice by invoice number
     *
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    /**
     * Get overdue invoices
     *
     * @return Collection
     */
    public function overdueInvoices(): Collection
    {
        return $this->model
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Get invoices by customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function findByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get invoices by status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get unpaid invoices
     *
     * @return Collection
     */
    public function unpaidInvoices(): Collection
    {
        return $this->model
            ->whereIn('status', ['sent', 'overdue'])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->get();
    }

    protected function getFilterableColumns(): array
    {
        return ['status', 'customer_id'];
    }
}
