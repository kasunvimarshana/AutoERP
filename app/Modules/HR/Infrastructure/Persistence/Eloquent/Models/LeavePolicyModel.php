<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class LeavePolicyModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_leave_policies';

    protected $fillable = ['tenant_id', 'leave_type_id', 'name', 'accrual_type', 'accrual_amount', 'org_unit_id', 'is_active', 'metadata'];

    protected $casts = ['accrual_amount' => 'float', 'is_active' => 'boolean', 'metadata' => 'array'];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveTypeModel::class, 'leave_type_id');
    }
}
