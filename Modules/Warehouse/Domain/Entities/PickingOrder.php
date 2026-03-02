<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PickingOrder entity.
 *
 * Represents a warehouse picking order (batch/wave/zone strategy).
 */
class PickingOrder extends Model
{
    use HasTenant;

    protected $table = 'picking_orders';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'picking_type',
        'status',
        'reference_type',
        'reference_id',
        'assigned_to',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PickingOrderLine::class);
    }
}
