<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class InventoryAdjustmentModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'inventory_adjustments';

    protected $fillable = [
        'tenant_id', 'adjustment_number', 'adjustment_date', 'warehouse_id',
        'type', 'status', 'reason', 'notes', 'confirmed_by', 'confirmed_at', 'metadata',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'confirmed_at'    => 'datetime',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentLineModel::class, 'adjustment_id');
    }
}
