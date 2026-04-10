<?php

namespace App\Modules\Procurement\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoodsReceipt extends BaseModel
{
    protected $table = 'goods_receipts';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'notes',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Procurement\Models\PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class, 'warehouse_id');
    }
}
