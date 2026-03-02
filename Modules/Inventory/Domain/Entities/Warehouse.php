<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Warehouse entity.
 *
 * Represents a physical warehouse belonging to a tenant.
 */
class Warehouse extends Model
{
    use HasTenant;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'location_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stockLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }
}
