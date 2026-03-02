<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PutawayRule entity.
 *
 * Defines rules for intelligently directing incoming stock to a specific zone.
 */
class PutawayRule extends Model
{
    use HasTenant;

    protected $table = 'putaway_rules';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'product_type',
        'zone_id',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority'  => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }
}
