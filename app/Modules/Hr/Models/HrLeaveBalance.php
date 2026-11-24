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
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrLeaveBalance extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_leave_balances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'calendar_year' => 'integer',
            'opening_balance' => 'decimal:2',
            'accrued_days' => 'decimal:2',
            'carried_forward_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'pending_approval_days' => 'decimal:2',
            'expired_days' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }
}
