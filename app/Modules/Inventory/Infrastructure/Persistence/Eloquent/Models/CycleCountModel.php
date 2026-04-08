<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CycleCountModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'cycle_counts';

    protected $fillable = [
        'tenant_id', 'count_number', 'warehouse_id', 'location_id',
        'status', 'counted_at', 'completed_at', 'counted_by', 'notes',
    ];

    protected $casts = [
        'counted_at'   => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(CycleCountLineModel::class, 'cycle_count_id');
    }
}
