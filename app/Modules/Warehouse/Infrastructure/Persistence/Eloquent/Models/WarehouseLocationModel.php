<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class WarehouseLocationModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'parent_id', 'code', 'name', 'type',
        'barcode', 'description', 'is_active', 'is_pickable', 'is_receivable',
        'max_weight', 'max_volume', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_pickable'  => 'boolean',
        'is_receivable' => 'boolean',
        'sort_order'   => 'integer',
        'max_weight'   => 'decimal:4',
        'max_volume'   => 'decimal:4',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
