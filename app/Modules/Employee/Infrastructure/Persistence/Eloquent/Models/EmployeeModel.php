<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EmployeeModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'employees';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'employee_code',
        'org_unit_id',
        'job_title',
        'hire_date',
        'termination_date',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'org_unit_id' => 'integer',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }
}
