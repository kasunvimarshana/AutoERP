<?php

declare(strict_types=1);

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Inventory\Database\Factories\UnitOfMeasureFactory;

/**
 * Unit of Measure Entity
 *
 * Represents a unit of measure (UoM) for products with support
 * for UoM categories and conversions.
 */
class UnitOfMeasure extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'uom_category',
        'ratio',
        'is_base_unit',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ratio' => 'decimal:6',
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['name'];

    /**
     * Get products using this UoM.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_of_measure_id');
    }

    /**
     * Check if this is the base unit for its category.
     */
    public function isBaseUnit(): bool
    {
        return $this->is_base_unit;
    }

    /**
     * Convert quantity from this UoM to base UoM.
     */
    public function toBaseUnit(float $quantity): float
    {
        return $quantity * $this->ratio;
    }

    /**
     * Convert quantity from base UoM to this UoM.
     */
    public function fromBaseUnit(float $quantity): float
    {
        if ($this->ratio == 0) {
            return 0;
        }

        return $quantity / $this->ratio;
    }

    /**
     * Convert quantity to another UoM in the same category.
     *
     * @param  float  $quantity  The quantity in the current UoM
     * @param  UnitOfMeasure  $targetUom  The target UoM to convert to
     * @return float|null The converted quantity, or null if UoMs are in different categories
     */
    public function convertTo(float $quantity, UnitOfMeasure $targetUom): ?float
    {
        if ($this->uom_category !== $targetUom->uom_category) {
            return null; // Cannot convert between different categories
        }

        $baseQuantity = $this->toBaseUnit($quantity);

        return $targetUom->fromBaseUnit($baseQuantity);
    }

    /**
     * Get all UoMs in the same category.
     */
    public function getSameCategoryUoms()
    {
        return self::where('uom_category', $this->uom_category)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UnitOfMeasureFactory
    {
        return UnitOfMeasureFactory::new();
    }
}
