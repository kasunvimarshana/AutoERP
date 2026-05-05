<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceMaintenancePlanModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_maintenance_plans';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'plan_code',
        'plan_name',
        'description',
        'asset_id',
        'product_id',
        'trigger_type',
        'interval_days',
        'interval_km',
        'interval_hours',
        'advance_notice_days',
        'last_serviced_at',
        'next_service_due_at',
        'last_service_odometer',
        'next_service_odometer',
        'assigned_employee_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'product_id' => 'integer',
        'assigned_employee_id' => 'integer',
        'interval_days' => 'integer',
        'advance_notice_days' => 'integer',
        'interval_km' => 'decimal:6',
        'interval_hours' => 'decimal:6',
        'last_service_odometer' => 'decimal:6',
        'next_service_odometer' => 'decimal:6',
        'last_serviced_at' => 'datetime',
        'next_service_due_at' => 'datetime',
        'metadata' => 'array',
    ];
}
