<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Invoice\Models\InvoicePayment;

class InvoicePaymentRepository extends BaseRepository implements InvoicePaymentRepositoryInterface
{
    /**
     * InvoicePaymentRepository constructor.
     */
    public function __construct(InvoicePayment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get payments by invoice.
     */
    public function getByInvoice(int $invoiceId)
    {
        return $this->model
            ->where('invoice_id', $invoiceId)
            ->with(['currency', 'creator'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get total paid amount for invoice.
     */
    public function getTotalPaidForInvoice(int $invoiceId): float
    {
        return (float) $this->model
            ->where('invoice_id', $invoiceId)
            ->sum('amount_in_invoice_currency');
    }
}
