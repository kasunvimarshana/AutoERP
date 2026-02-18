<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Invoice;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Invoice Repository Implementation
 *
 * Handles all invoice data access operations.
 */
class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    /**
     * InvoiceRepository constructor.
     */
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->orderBy('invoice_date', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOverdueInvoices(): Collection
    {
        return $this->model
            ->where('due_date', '<', now())
            ->where('amount_due', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnpaidInvoices(): Collection
    {
        return $this->model
            ->where('amount_due', '>', 0)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        return $this->model
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getAgingReport(): Collection
    {
        return $this->model
            ->selectRaw('
                customer_id,
                SUM(CASE WHEN DATEDIFF(CURRENT_DATE, due_date) <= 0 THEN amount_due ELSE 0 END) as current,
                SUM(CASE WHEN DATEDIFF(CURRENT_DATE, due_date) BETWEEN 1 AND 30 THEN amount_due ELSE 0 END) as days_1_30,
                SUM(CASE WHEN DATEDIFF(CURRENT_DATE, due_date) BETWEEN 31 AND 60 THEN amount_due ELSE 0 END) as days_31_60,
                SUM(CASE WHEN DATEDIFF(CURRENT_DATE, due_date) BETWEEN 61 AND 90 THEN amount_due ELSE 0 END) as days_61_90,
                SUM(CASE WHEN DATEDIFF(CURRENT_DATE, due_date) > 90 THEN amount_due ELSE 0 END) as over_90,
                SUM(amount_due) as total
            ')
            ->where('amount_due', '>', 0)
            ->groupBy('customer_id')
            ->with('customer')
            ->get();
    }
}
