<?php

namespace App\Modules\Inventory\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit of Measure Model
 *
 * Represents a unit of measurement for products.
 */
class UnitOfMeasure extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'units';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'is_base',
        'conversion_factor',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:6',
    ];

    /**
     * Get the tenant that owns the unit of measure.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for base units
     */
    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }
}
