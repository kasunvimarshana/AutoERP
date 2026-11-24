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
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrCompOffClaim extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_comp_off_claims';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'worked_date' => 'date',
            'attendance_record_id' => 'integer',
            'claimed_days' => 'decimal:2',
            'status' => CompOffStatus::class,
            'expiry_date' => 'date',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceRecord::class, 'attendance_record_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }
}
