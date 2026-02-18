<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Inventory\Enums\LocationType;

/**
 * Location Model
 *
 * Represents a storage location within a warehouse.
 */
class Location extends BaseModel
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'code',
        'name',
        'location_type',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'location_type' => LocationType::class,
        'capacity' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
}
