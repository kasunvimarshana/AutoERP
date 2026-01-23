<?php

namespace App\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\StockMovementFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'movement_number',
        'inventory_item_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'from_location',
        'to_location',
        'notes',
        'created_by',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (empty($movement->uuid)) {
                $movement->uuid = Str::uuid();
            }
            if (empty($movement->movement_number)) {
                $movement->movement_number = static::generateMovementNumber();
            }
        });
    }

    /**
     * Generate unique movement number
     */
    protected static function generateMovementNumber(): string
    {
        do {
            $code = 'MOV-' . strtoupper(Str::random(8));
        } while (static::where('movement_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the stock movement
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the inventory item for the stock movement
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who created the stock movement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get movement direction attribute
     */
    public function getMovementDirectionAttribute(): string
    {
        return in_array($this->movement_type, ['purchase', 'return', 'adjustment']) && $this->quantity > 0
            ? 'in'
            : 'out';
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
     * Scope: By movement type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope: By inventory item
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    /**
     * Scope: Recent movements
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('movement_date', '>=', now()->subDays($days));
    }
}
