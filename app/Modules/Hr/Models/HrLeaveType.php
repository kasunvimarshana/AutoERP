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
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrLeaveType extends TenantOwnedModel
{
    use ScopesHrTenant;
    use SoftDeletes;

    protected $table = 'hr_leave_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'attachment_mandatory_after_days' => 'integer',
            'allow_half_day' => 'boolean',
            'allow_hourly' => 'boolean',
            'is_compensatory' => 'boolean',
            'sandwich_rule_applied' => 'boolean',
        ]);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(HrLeavePolicy::class, 'leave_type_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(HrLeaveBalance::class, 'leave_type_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'leave_type_id');
    }
}
