<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * BinLocation entity.
 *
 * Represents a specific bin within a warehouse zone.
 * capacity is cast to string to enforce BCMath arithmetic.
 */
class BinLocation extends Model
{
    use HasTenant;

    protected $table = 'bin_locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_zone_id',
        'aisle',
        'row',
        'level',
        'bin_code',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity'  => 'string',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id');
    }
}
