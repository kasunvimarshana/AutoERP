<?php

namespace App\Modules\Billing\Repositories;

use App\Core\BaseRepository;
use App\Modules\Billing\Models\Invoice;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->findBy('invoice_number', $invoiceNumber);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get();
    }

    public function getOverdueInvoices(int $tenantId)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '<', now())
            ->get();
    }

    public function getTotalRevenue(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid');

        if ($startDate && $endDate) {
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        return $query->sum('total_amount');
    }

    public function getTotalOutstanding(int $tenantId)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('balance');
    }
}
