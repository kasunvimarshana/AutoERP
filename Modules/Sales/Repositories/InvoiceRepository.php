<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Exceptions\InvoiceNotFoundException;
use Modules\Sales\Models\Invoice;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Invoice::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return InvoiceNotFoundException::class;
    }

    public function findByCode(string $code): ?Invoice
    {
        return $this->model->where('invoice_code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Invoice
    {
        $invoice = $this->findByCode($code);

        if (! $invoice) {
            throw new InvoiceNotFoundException("Invoice with code {$code} not found");
        }

        return $invoice;
    }

    public function getByStatus(InvoiceStatus $status, int $perPage = 15)
    {
        return $this->model->ofStatus($status)->latest()->paginate($perPage);
    }

    public function getByCustomer(string $customerId, int $perPage = 15)
    {
        return $this->model->forCustomer($customerId)->latest()->paginate($perPage);
    }

    public function getUnpaid(int $perPage = 15)
    {
        return $this->model->unpaid()->latest()->paginate($perPage);
    }

    public function getOverdue(int $perPage = 15)
    {
        return $this->model->overdue()->latest()->paginate($perPage);
    }

    public function getTotalUnpaidAmount(): string
    {
        return (string) $this->model->unpaid()->sum('total_amount');
    }

    public function getTotalOverdueAmount(): string
    {
        return (string) $this->model->overdue()->sum('total_amount');
    }

    public function getFiltered(array $filters, int $perPage = 15)
    {
        $query = $this->model->newQuery()
            ->with(['organization', 'customer', 'items.product', 'order']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['overdue'])) {
            $query->where('status', InvoiceStatus::OVERDUE);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('invoice_date')->paginate($perPage);
    }
}
