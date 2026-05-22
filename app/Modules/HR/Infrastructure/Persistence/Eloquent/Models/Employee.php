<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes as HasTenantAndOrganizationScopesTrait;

class Employee extends Model
{
    use HasTenantAndOrganizationScopesTrait;
    use SoftDeletes;

    protected $table = 'employees';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'hire_date' => 'date',
            'confirmation_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Department', 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Designation', 'designation_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\EmploymentType', 'employment_type_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo('Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Country', 'country_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\EmployeeContact', 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\EmployeeDocument', 'employee_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\EmployeeContract', 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'updated_by');
    }
}
