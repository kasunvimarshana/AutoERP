<?php

namespace Modules\FieldService\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ServiceOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'fs_service_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'service_team_id',
        'reference_no',
        'title',
        'description',
        'customer_id',
        'contact_name',
        'contact_phone',
        'location',
        'technician_id',
        'status',
        'duration_hours',
        'labor_cost',
        'parts_cost',
        'resolution_notes',
        'scheduled_at',
        'started_at',
        'completed_at',
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
