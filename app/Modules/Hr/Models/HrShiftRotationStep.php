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

final class HrShiftRotationStep extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_rotation_steps';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'shift_rotation_id' => 'integer',
            'day_sequence' => 'integer',
            'shift_id' => 'integer',
        ]);
    }

    public function rotation(): BelongsTo
    {
        return $this->belongsTo(HrShiftRotation::class, 'shift_rotation_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }
}
