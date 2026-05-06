<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class LeaveTypeModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_leave_types';

    protected $fillable = ['tenant_id', 'name', 'code', 'description', 'max_days_per_year', 'carry_forward_days', 'is_paid', 'requires_approval', 'applicable_gender', 'min_service_days', 'is_active', 'metadata', 'row_version'];

    protected $casts = ['tenant_id' => 'integer', 'row_version' => 'integer', 'max_days_per_year' => 'float', 'carry_forward_days' => 'float', 'is_paid' => 'boolean', 'requires_approval' => 'boolean', 'min_service_days' => 'integer', 'is_active' => 'boolean', 'metadata' => 'array'];
}
