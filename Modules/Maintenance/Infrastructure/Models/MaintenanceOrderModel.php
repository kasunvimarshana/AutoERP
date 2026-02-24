<?php

namespace Modules\Maintenance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class MaintenanceOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'maintenance_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'reference',
        'equipment_id',
        'order_type',
        'description',
        'scheduled_date',
        'assigned_to',
        'labor_cost',
        'parts_cost',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
