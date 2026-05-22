<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class LeavePolicyLine extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'leave_policy_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'annual_allocation' => 'decimal:4',
            'accrual_amount' => 'decimal:4',
            'carry_forward_max' => 'decimal:4',
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

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\LeavePolicy', 'leave_policy_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\LeaveType', 'leave_type_id');
    }
}
