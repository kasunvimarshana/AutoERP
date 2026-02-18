<?php

namespace App\Modules\Procurement\Models;

use App\Models\Tenant;
use App\Modules\Procurement\Enums\PurchaseOrderStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Procurement\PurchaseOrderFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'warehouse_id',
        'po_number',
        'po_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'approved_by',
        'approved_at',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'status' => PurchaseOrderStatus::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeByStatus($query, PurchaseOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', PurchaseOrderStatus::PENDING);
    }
}
