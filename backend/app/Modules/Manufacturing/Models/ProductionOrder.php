<?php

namespace App\Modules\Manufacturing\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Manufacturing\Enums\ProductionOrderPriority;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Manufacturing\ProductionOrderFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'production_order_number',
        'product_id',
        'bill_of_material_id',
        'quantity',
        'warehouse_id',
        'scheduled_start_date',
        'scheduled_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'priority',
        'notes',
        'created_by',
        'released_by',
        'released_at',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_start_date' => 'datetime',
        'scheduled_end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => ProductionOrderStatus::class,
        'priority' => ProductionOrderPriority::class,
        'quantity' => 'decimal:4',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeByStatus($query, ProductionOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, ProductionOrderPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', ProductionOrderStatus::IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ProductionOrderStatus::COMPLETED);
    }
}
