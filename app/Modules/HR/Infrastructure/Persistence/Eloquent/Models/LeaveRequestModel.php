<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class LeaveRequestModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_leave_requests';

    protected $fillable = [
        'tenant_id', 'employee_id', 'leave_type', 'start_date', 'end_date',
        'reason', 'status', 'approved_by', 'approved_at', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'employee_id' => 'integer',
        'approved_by' => 'integer',
        'metadata'    => 'array',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];
}
