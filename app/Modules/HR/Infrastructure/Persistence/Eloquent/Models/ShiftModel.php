<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ShiftModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_shifts';

    protected $fillable = ['tenant_id', 'name', 'code', 'shift_type', 'start_time', 'end_time', 'break_duration', 'work_days', 'grace_minutes', 'overtime_threshold', 'is_night_shift', 'metadata', 'is_active'];

    protected $casts = ['work_days' => 'array', 'metadata' => 'array', 'is_night_shift' => 'boolean', 'is_active' => 'boolean', 'break_duration' => 'integer', 'grace_minutes' => 'integer', 'overtime_threshold' => 'integer'];
}
