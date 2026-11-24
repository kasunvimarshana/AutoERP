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
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrShiftSwap extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_swaps';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'requester_employee_id' => 'integer',
            'target_employee_id' => 'integer',
            'requester_shift_id' => 'integer',
            'target_shift_id' => 'integer',
            'swap_date' => 'date',
            'target_swap_date' => 'date',
            'status' => ShiftSwapStatus::class,
            'peer_actioned_at' => 'datetime',
            'manager_actioned_by' => 'integer',
            'manager_actioned_at' => 'datetime',
        ]);
    }

    public function requesterEmployee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'requester_employee_id');
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'target_employee_id');
    }

    public function requesterShift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'requester_shift_id');
    }

    public function targetShift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'target_shift_id');
    }

    public function managerActioner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'manager_actioned_by');
    }
}
