<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceTaskModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_tasks';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'service_work_order_id',
        'line_number',
        'task_code',
        'description',
        'estimated_hours',
        'actual_hours',
        'status',
        'assigned_employee_id',
        'labor_rate',
        'labor_amount',
        'commission_amount',
        'incentive_amount',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'service_work_order_id' => 'integer',
        'line_number' => 'integer',
        'assigned_employee_id' => 'integer',
        'estimated_hours' => 'decimal:6',
        'actual_hours' => 'decimal:6',
        'labor_rate' => 'decimal:6',
        'labor_amount' => 'decimal:6',
        'commission_amount' => 'decimal:6',
        'incentive_amount' => 'decimal:6',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
