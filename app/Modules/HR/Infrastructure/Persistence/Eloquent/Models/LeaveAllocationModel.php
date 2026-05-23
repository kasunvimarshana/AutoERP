<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class LeaveAllocationModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'leave_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allocated_days' => 'decimal:4',
            'carried_forward' => 'decimal:4',
            'created_by' => 'integer',
            'employee_id' => 'integer',
            'expiry_date' => 'date',
            'leave_type_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'pending_days' => 'decimal:4',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'used_days' => 'decimal:4',
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

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveTypeModel::class, 'leave_type_id');
    }
}
