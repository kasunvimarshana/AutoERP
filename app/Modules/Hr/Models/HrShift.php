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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\ShiftStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrShift extends TenantOwnedModel
{
    use ScopesHrTenant;
    use SoftDeletes;

    protected $table = 'hr_shifts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_overnight' => 'boolean',
            'grace_in_minutes' => 'integer',
            'grace_out_minutes' => 'integer',
            'half_day_threshold_minutes' => 'integer',
            'full_day_threshold_minutes' => 'integer',
            'is_break_paid' => 'boolean',
            'auto_deduct_break' => 'boolean',
            'auto_deduct_break_minutes' => 'integer',
            'auto_deduct_threshold_minutes' => 'integer',
            'is_flexible' => 'boolean',
            'status' => ShiftStatus::class,
        ]);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(HrShiftBreak::class, 'shift_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HrShiftAssignment::class, 'shift_id');
    }
}
