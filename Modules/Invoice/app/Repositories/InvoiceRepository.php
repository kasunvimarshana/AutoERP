<?php

declare(strict_types=1);

namespace Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Models\Invoice;

/**
 * Invoice Repository
 *
 * Handles data access for Invoice model
 */
class InvoiceRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Invoice;
    }

    /**
     * Find invoice by invoice number
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        /** @var Invoice|null */
        return $this->findOneBy(['invoice_number' => $invoiceNumber]);
    }

    /**
     * Check if invoice number exists
     */
    public function invoiceNumberExists(string $invoiceNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('invoice_number', $invoiceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get invoices for customer
     */
    public function getForCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()->where('customer_id', $customerId)->get();
    }

    /**
     * Get invoices for branch
     */
    public function getForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()->where('branch_id', $branchId)->get();
    }

    /**
     * Get outstanding invoices
     */
    public function getOutstanding(): Collection
    {
        return $this->model->newQuery()->outstanding()->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection
    {
        return $this->model->newQuery()->overdue()->get();
    }

    /**
     * Find invoice with all relations
     */
    public function findWithRelations(int $id): ?Invoice
    {
        /** @var Invoice|null */
        return $this->model->newQuery()
            ->with(['customer', 'vehicle', 'branch', 'jobCard', 'items', 'payments', 'commissions'])
            ->find($id);
    }

    /**
     * Get invoices with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function getWithFilters(array $filters): Collection
    {
        $query = $this->model->newQuery()->with(['customer', 'vehicle', 'branch']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }

        if (isset($filters['outstanding']) && $filters['outstanding']) {
            $query->outstanding();
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        return $query->latest()->get();
    }
}
