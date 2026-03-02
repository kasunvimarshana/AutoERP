<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * SalesInvoice entity.
 *
 * Monetary amounts are cast to string for BCMath precision.
 */
class SalesInvoice extends Model
{
    use HasTenant;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_date',
        'total_amount',
        'paid_amount',
    ];

    protected $casts = [
        'issued_at'    => 'date',
        'due_date'     => 'date',
        'total_amount' => 'string',
        'paid_amount'  => 'string',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
