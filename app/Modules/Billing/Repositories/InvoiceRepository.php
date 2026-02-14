<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Invoice::class;
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get invoices by customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection
    {
        return $this->model->where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();
    }

    /**
     * Get invoices by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('invoice_date', [$startDate, $endDate])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get total outstanding amount
     */
    public function getTotalOutstanding(?int $customerId = null): float
    {
        $query = $this->model->where('status', 'pending');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query->sum('total_amount');
    }
}
