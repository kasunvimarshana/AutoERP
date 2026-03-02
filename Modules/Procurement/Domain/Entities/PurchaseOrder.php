<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PurchaseOrder entity.
 *
 * All monetary amounts are cast to string to enforce BCMath precision.
 */
class PurchaseOrder extends Model
{
    use HasTenant;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'order_number',
        'status',
        'order_date',
        'expected_delivery_date',
        'currency_code',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'vendor_id'              => 'integer',
        'order_date'             => 'date',
        'expected_delivery_date' => 'date',
        'subtotal'               => 'string',
        'tax_amount'             => 'string',
        'total_amount'           => 'string',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class, 'purchase_order_id');
    }
}
