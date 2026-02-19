<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use App\Core\Traits\AuditTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UoM Conversion Model
 *
 * Defines conversion factors between units of measure.
 *
 * @property int $id
 * @property int $from_uom_id
 * @property int $to_uom_id
 * @property float $conversion_factor
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class UoMConversion extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'conversion_factor',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:10',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the source unit
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_uom_id');
    }

    /**
     * Get the target unit
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_uom_id');
    }

    /**
     * Convert a quantity using this conversion
     */
    public function convert(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Scope to filter active conversions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find conversion between two units
     */
    public function scopeBetweenUnits($query, int $fromUomId, int $toUomId)
    {
        return $query->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId);
    }
}
