<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveAllocationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyLineModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class LeaveTypeModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'leave_types';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'allow_negative_balance' => 'boolean',
            'carry_forward_max' => 'decimal:4',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'max_days_per_year' => 'decimal:4',
            'metadata' => 'array',
            'min_service_days' => 'integer',
            'requires_approval' => 'boolean',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocationModel::class, 'leave_type_id');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplicationModel::class, 'leave_type_id');
    }

    public function leavePolicyLines(): HasMany
    {
        return $this->hasMany(LeavePolicyLineModel::class, 'leave_type_id');
    }

}
