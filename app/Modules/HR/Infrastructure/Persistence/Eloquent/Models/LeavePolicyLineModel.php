<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class LeavePolicyLineModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'leave_policy_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'accrual_amount' => 'decimal:4',
            'annual_allocation' => 'decimal:4',
            'carry_forward_max' => 'decimal:4',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicyModel::class, 'leave_policy_id');
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
