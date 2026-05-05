<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class WarehouseLocationModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'name',
        'code',
        'path',
        'depth',
        'type',
        'is_active',
        'is_pickable',
        'is_receivable',
        'capacity',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'warehouse_id' => 'integer',
        'parent_id' => 'integer',
        'depth' => 'integer',
        'is_active' => 'boolean',
        'is_pickable' => 'boolean',
        'is_receivable' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
