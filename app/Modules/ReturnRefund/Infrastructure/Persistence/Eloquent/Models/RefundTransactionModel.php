<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class RefundTransactionModel extends Model
{
    protected $table = 'refund_transactions';

    protected $fillable = [
        'id',
        'tenant_id',
        'rental_transaction_id',
        'refund_number',
        'gross_amount',
        'adjustment_amount',
        'net_refund_amount',
        'status',
        'finance_reference_id',
        'processed_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:6',
        'adjustment_amount' => 'decimal:6',
        'net_refund_amount' => 'decimal:6',
        'processed_at' => 'datetime',
    ];

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
