<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Models\Invoice;
use Modules\Core\Repositories\BaseRepository;

/**
 * Invoice Repository
 */
class InvoiceRepository extends BaseRepository
{
    protected function model(): string
    {
        return Invoice::class;
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->newQuery()->where('invoice_number', $invoiceNumber)->first();
    }

    public function getOverdueInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()
            ->where('status', 'overdue')
            ->orWhere(function ($query) {
                $query->whereIn('status', ['sent', 'partial'])
                    ->where('due_date', '<', now());
            })
            ->orderBy('due_date')
            ->get();
    }

    public function getUnpaidInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->orderBy('due_date')
            ->get();
    }
}
