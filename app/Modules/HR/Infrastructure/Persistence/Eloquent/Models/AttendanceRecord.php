<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class AttendanceRecord extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'attendance_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'break_duration' => 'integer',
            'worked_minutes' => 'integer',
            'overtime_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Employee', 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Shift', 'shift_id');
    }
}
