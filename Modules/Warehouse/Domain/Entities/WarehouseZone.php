<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WarehouseZone entity.
 *
 * Represents a named zone within a warehouse (storage, receiving, shipping, quarantine).
 */
class WarehouseZone extends Model
{
    use HasTenant;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'name',
        'code',
        'zone_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class, 'warehouse_zone_id');
    }
}
