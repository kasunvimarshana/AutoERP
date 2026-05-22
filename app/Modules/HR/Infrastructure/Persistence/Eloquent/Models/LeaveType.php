<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class LeaveType extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'leave_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'max_days_per_year' => 'decimal:4',
            'carry_forward_max' => 'decimal:4',
            'allow_negative_balance' => 'boolean',
            'min_service_days' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'created_by');
    }

    public function policyLines(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\LeavePolicyLine', 'leave_type_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\LeaveAllocation', 'leave_type_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\LeaveApplication', 'leave_type_id');
    }
}
