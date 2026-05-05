<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceLaborEntryModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_labor_entries';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'service_work_order_id',
        'service_task_id',
        'employee_id',
        'started_at',
        'ended_at',
        'hours_worked',
        'labor_rate',
        'labor_amount',
        'commission_rate',
        'commission_amount',
        'incentive_amount',
        'status',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'service_work_order_id' => 'integer',
        'service_task_id' => 'integer',
        'employee_id' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'hours_worked' => 'decimal:6',
        'labor_rate' => 'decimal:6',
        'labor_amount' => 'decimal:6',
        'commission_rate' => 'decimal:6',
        'commission_amount' => 'decimal:6',
        'incentive_amount' => 'decimal:6',
        'metadata' => 'array',
    ];
}
