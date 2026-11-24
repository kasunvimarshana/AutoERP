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

final class HrShiftRotationAssignment extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_rotation_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'shift_rotation_id' => 'integer',
            'anchor_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function rotation(): BelongsTo
    {
        return $this->belongsTo(HrShiftRotation::class, 'shift_rotation_id');
    }
}
