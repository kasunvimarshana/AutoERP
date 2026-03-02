<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * VendorBill entity.
 *
 * All monetary amounts are cast to string to enforce BCMath precision.
 */
class VendorBill extends Model
{
    use HasTenant;

    protected $table = 'vendor_bills';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'purchase_order_id',
        'bill_number',
        'status',
        'bill_date',
        'due_date',
        'total_amount',
        'paid_amount',
    ];

    protected $casts = [
        'vendor_id'         => 'integer',
        'purchase_order_id' => 'integer',
        'bill_date'         => 'date',
        'due_date'          => 'date',
        'total_amount'      => 'string',
        'paid_amount'       => 'string',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
