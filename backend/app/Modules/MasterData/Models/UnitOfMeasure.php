<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class UnitOfMeasure extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'units_of_measure';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'abbreviation',
        'type',
        'base_unit_id',
        'conversion_factor',
        'is_system',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the base unit of measure.
     */
    public function baseUnit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    /**
     * Get derived units.
     */
    public function derivedUnits()
    {
        return $this->hasMany(UnitOfMeasure::class, 'base_unit_id');
    }

    /**
     * Get the user who created the unit.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the unit.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if unit is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this is a system unit.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if this is a base unit.
     *
     * @return bool
     */
    public function isBaseUnit(): bool
    {
        return $this->base_unit_id === null;
    }

    /**
     * Convert quantity from this unit to base unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertToBase(float $quantity): float
    {
        if ($this->isBaseUnit()) {
            return $quantity;
        }
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit to this unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertFromBase(float $quantity): float
    {
        if ($this->isBaseUnit() || $this->conversion_factor == 0) {
            return $quantity;
        }
        return $quantity / $this->conversion_factor;
    }

    /**
     * Convert quantity from this unit to another unit.
     *
     * @param float $quantity
     * @param UnitOfMeasure $toUnit
     * @return float
     */
    public function convertTo(float $quantity, UnitOfMeasure $toUnit): float
    {
        // Convert to base unit first
        $baseQuantity = $this->convertToBase($quantity);
        // Then convert from base to target unit
        return $toUnit->convertFromBase($baseQuantity);
    }
}
