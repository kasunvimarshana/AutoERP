<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\Invoice;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository extends BaseRepository
{
    protected function model(): string
    {
        return Invoice::class;
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        $query = $this->model->where('customer_id', $customerId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }

        return $query->with(['items.product', 'customer', 'payments'])->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with(['items.product', 'customer', 'payments'])
            ->get();
    }

    public function findOverdueInvoices(): Collection
    {
        return $this->model
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now())
            ->where('balance_amount', '>', 0)
            ->with(['items.product', 'customer', 'payments'])
            ->get();
    }

    public function generateInvoiceNumber(): string
    {
        $lastInvoice = $this->model->orderBy('id', 'desc')->first();
        $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 4)) : 0;

        return 'INV-'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}
