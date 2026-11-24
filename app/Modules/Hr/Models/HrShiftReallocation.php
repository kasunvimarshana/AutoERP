<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\ShiftReallocationReason;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrShiftReallocation extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_reallocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'absent_employee_id' => 'integer',
            'cover_employee_id' => 'integer',
            'shift_id' => 'integer',
            'reallocation_date' => 'date',
            'reason_code' => ShiftReallocationReason::class,
            'reallocated_by' => 'integer',
        ]);
    }

    public function absentEmployee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'absent_employee_id');
    }

    public function coverEmployee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'cover_employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }

    public function reallocator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reallocated_by');
    }
}
