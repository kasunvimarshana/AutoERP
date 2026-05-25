<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AttendanceRecordModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope;

    protected $table = 'attendance_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'break_duration' => 'integer',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'employee_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'overtime_minutes' => 'integer',
            'row_version' => 'integer',
            'shift_id' => 'integer',
            'tenant_id' => 'integer',
            'worked_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftModel::class, 'shift_id');
    }
}

