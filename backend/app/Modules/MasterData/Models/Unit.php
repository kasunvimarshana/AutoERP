<?php

namespace App\Modules\MasterData\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'symbol',
        'type',
        'base_unit_id',
        'conversion_factor',
        'description',
        'is_system',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:8',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the unit.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the base unit for conversion.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get derived units (units that use this as base).
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * Scope to get only active units.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Convert value from this unit to base unit.
     */
    public function toBaseUnit(float $value): float
    {
        return $value * $this->conversion_factor;
    }

    /**
     * Convert value from base unit to this unit.
     */
    public function fromBaseUnit(float $value): float
    {
        return $this->conversion_factor > 0 ? $value / $this->conversion_factor : 0;
    }
}
