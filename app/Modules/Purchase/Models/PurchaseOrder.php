<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\PurchaseOrderStatus;

final class PurchaseOrder extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'supplier_id' => 'integer',
            'purchase_order_date' => 'date',
            'expected_delivery_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'charge_total' => 'decimal:6',
            'adjustment_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ]);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PurchaseHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'purchase_order');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'purchase_order_id');
    }
}
