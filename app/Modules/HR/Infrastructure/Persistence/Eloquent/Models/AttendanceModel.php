<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class AttendanceModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_attendance';

    protected $fillable = [
        'tenant_id', 'employee_id', 'date', 'check_in_time', 'check_out_time',
        'status', 'notes', 'hours_worked',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'employee_id' => 'integer',
        'hours_worked' => 'float',
    ];
}
