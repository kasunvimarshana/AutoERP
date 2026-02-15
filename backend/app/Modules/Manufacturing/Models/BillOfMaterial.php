<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\Product\Models\Product;
use App\Modules\MasterData\Models\UnitOfMeasure;
use App\Modules\Tenant\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BillOfMaterial Model
 * 
 * Represents a Bill of Materials (BOM) defining the components and quantities
 * required to manufacture a finished product.
 */
class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, HasUuid, Auditable;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'product_id',
        'bom_number',
        'name',
        'version',
        'status',
        'quantity',
        'unit_id',
        'production_time_minutes',
        'estimated_cost',
        'actual_cost',
        'notes',
        'instructions',
        'is_default',
        'valid_from',
        'valid_until',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'production_time_minutes' => 'integer',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'is_default' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the product this BOM produces
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the organization this BOM belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the unit of measure
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Get all BOM items (components)
     */
    public function items(): HasMany
    {
        return $this->hasMany(BOMItem::class, 'bom_id');
    }

    /**
     * Get all work orders using this BOM
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'bom_id');
    }

    /**
     * Scope to get active BOMs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get default BOM for a product
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get valid BOMs for current date
     */
    public function scopeValid($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', $date);
        });
    }

    /**
     * Calculate total material cost from BOM items
     */
    public function calculateTotalCost(): float
    {
        return $this->items->sum('total_cost');
    }

    /**
     * Check if BOM is currently valid
     */
    public function isValid($date = null): bool
    {
        $date = $date ?? now();
        $validFrom = $this->valid_from ? $this->valid_from->startOfDay() : null;
        $validUntil = $this->valid_until ? $this->valid_until->endOfDay() : null;

        if ($validFrom && $date < $validFrom) {
            return false;
        }

        if ($validUntil && $date > $validUntil) {
            return false;
        }

        return true;
    }
}
