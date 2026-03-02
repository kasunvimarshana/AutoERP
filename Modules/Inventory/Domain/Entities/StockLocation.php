<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * StockLocation entity.
 *
 * Represents a named location (shelf/bin/rack/area) inside a warehouse.
 */
class StockLocation extends Model
{
    use HasTenant;

    protected $table = 'stock_locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
