<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit of Measure Model
 *
 * Represents units used for product measurements (kg, liter, piece, etc.)
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property bool $is_base_unit
 * @property bool $is_active
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class UnitOfMeasure extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'type',
        'is_base_unit',
        'is_active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_base_unit' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get conversions from this unit
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UoMConversion::class, 'from_uom_id');
    }

    /**
     * Get conversions to this unit
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UoMConversion::class, 'to_uom_id');
    }

    /**
     * Get products that buy in this unit
     */
    public function productsBuyingInUnit(): HasMany
    {
        return $this->hasMany(Product::class, 'buy_unit_id');
    }

    /**
     * Get products that sell in this unit
     */
    public function productsSellingInUnit(): HasMany
    {
        return $this->hasMany(Product::class, 'sell_unit_id');
    }

    /**
     * Scope to filter active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get base units
     */
    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }
}
