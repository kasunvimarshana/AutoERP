<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrderModel extends Model
{
    use SoftDeletes;

    protected $table = 'service_orders';

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_id',
        'assigned_technician_id',
        'order_number',
        'service_type',
        'status',
        'description',
        'scheduled_at',
        'started_at',
        'completed_at',
        'estimated_cost',
        'total_cost',
        'version',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
        'version' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ServiceTaskModel::class, 'service_order_id', 'id');
    }

    public function partUsages(): HasMany
    {
        return $this->hasMany(ServicePartUsageModel::class, 'service_order_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
