<?php

namespace App\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PurchaseOrderFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'po_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->uuid)) {
                $order->uuid = Str::uuid();
            }
            if (empty($order->po_number)) {
                $order->po_number = static::generatePoNumber();
            }
        });
    }

    /**
     * Generate unique PO number
     */
    protected static function generatePoNumber(): string
    {
        do {
            $code = 'PO-' . strtoupper(Str::random(8));
        } while (static::where('po_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the purchase order
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the supplier for the purchase order
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created the purchase order
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved the purchase order
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the items for the purchase order
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Check if order is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return !is_null($this->approved_at) && !is_null($this->approved_by);
    }

    /**
     * Check if order is fully received
     */
    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Check if order is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_delivery_date) {
            return false;
        }

        return now()->isAfter($this->expected_delivery_date)
            && !in_array($this->status, ['received', 'cancelled']);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending orders
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted', 'approved', 'ordered']);
    }

    /**
     * Scope: Approved orders
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')->whereNotNull('approved_by');
    }

    /**
     * Scope: Overdue orders
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now())
            ->whereNotIn('status', ['received', 'cancelled']);
    }
}
