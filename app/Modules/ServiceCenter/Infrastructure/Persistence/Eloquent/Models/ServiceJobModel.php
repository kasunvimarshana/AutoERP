<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceJobModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_service_jobs';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'vehicle_id',
        'driver_id',
        'job_number',
        'job_type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'odometer_in',
        'odometer_out',
        'description',
        'parts_cost',
        'labour_cost',
        'total_cost',
        'technician_notes',
        'customer_approval',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'parts_cost'        => 'string',
        'labour_cost'       => 'string',
        'total_cost'        => 'string',
        'scheduled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
        'metadata'          => 'array',
        'customer_approval' => 'boolean',
        'is_active'         => 'boolean',
    ];
}
