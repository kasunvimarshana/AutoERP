<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class PerformanceReviewModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope, SoftDeletes;

    protected $table = 'performance_reviews';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'cycle_id' => 'integer',
            'employee_id' => 'integer',
            'goals' => 'array',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'reviewer_id' => 'integer',
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

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycleModel::class, 'cycle_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reviewer_id');
    }
}

