<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CycleCountHeaderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'cycle_count_headers';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'location_id',
        'status',
        'counted_by_user_id',
        'counted_at',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'warehouse_id' => 'integer',
        'location_id' => 'integer',
        'counted_by_user_id' => 'integer',
        'approved_by_user_id' => 'integer',
        'counted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(CycleCountLineModel::class, 'count_header_id');
    }
}
