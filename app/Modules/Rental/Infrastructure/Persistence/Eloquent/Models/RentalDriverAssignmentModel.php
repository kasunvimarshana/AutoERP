<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalDriverAssignmentModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_driver_assignments';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'employee_id',
        'substitute_for_assignment_id',
        'assignment_status',
        'assigned_from',
        'assigned_to',
        'substitution_reason',
        'assigned_by',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'employee_id' => 'integer',
        'substitute_for_assignment_id' => 'integer',
        'assigned_by' => 'integer',
        'assigned_from' => 'datetime',
        'assigned_to' => 'datetime',
        'metadata' => 'array',
    ];
}
