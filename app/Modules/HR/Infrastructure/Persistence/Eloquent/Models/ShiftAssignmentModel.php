<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ShiftAssignmentModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_shift_assignments';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'employee_id', 'shift_id', 'effective_from', 'effective_to'];

    protected $casts = ['org_unit_id' => 'integer', 'row_version' => 'integer', 'effective_from' => 'date', 'effective_to' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftModel::class, 'shift_id');
    }
}
