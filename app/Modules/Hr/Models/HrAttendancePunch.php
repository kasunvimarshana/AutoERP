<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrAttendancePunch extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_punches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'attendance_record_id' => 'integer',
            'employee_id' => 'integer',
            'attendance_date' => 'date',
            'punch_time' => 'datetime',
            'punch_type' => PunchType::class,
            'punch_source' => PunchSource::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_verified' => 'boolean',
            'is_ignored' => 'boolean',
        ]);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceRecord::class, 'attendance_record_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
