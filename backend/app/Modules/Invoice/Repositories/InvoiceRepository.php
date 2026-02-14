<?php

namespace App\Modules\Invoice\Repositories;

use App\Base\BaseRepository;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    public function findByCustomer(string $customerId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    public function findByOrder(string $orderId, array $relations = []): ?Invoice
    {
        return $this->model->with($relations)
            ->where('order_id', $orderId)
            ->first();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    public function findByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    public function getByStatus(string $status, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', $status)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    public function getByPaymentStatus(string $paymentStatus, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('payment_status', $paymentStatus)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    public function getOverdue(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('due_date', '<', now())
            ->where('payment_status', '!=', 'paid')
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getByDateRange(string $from, string $to, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }
}
