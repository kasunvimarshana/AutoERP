<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class AttendanceLogModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'attendance_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'biometric_device_id' => 'integer',
            'employee_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'processed_at' => 'datetime',
            'punch_time' => 'datetime',
            'raw_data' => 'array',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
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

    public function biometricDevice(): BelongsTo
    {
        return $this->belongsTo(BiometricDeviceModel::class, 'biometric_device_id');
    }
}
