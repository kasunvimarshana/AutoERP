<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class LeaveAllocationModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'leave_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allocated_days' => 'decimal:4',
            'carried_forward' => 'decimal:4',
            'expiry_date' => 'date',
            'metadata' => 'array',
            'pending_days' => 'decimal:4',
            'row_version' => 'integer',
            'used_days' => 'decimal:4',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveTypeModel::class, 'leave_type_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
