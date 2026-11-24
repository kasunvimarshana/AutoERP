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
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrShiftRotation extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_rotations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'cycle_days' => 'integer',
        ]);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(HrShiftRotationStep::class, 'shift_rotation_id')->orderBy('day_sequence');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HrShiftRotationAssignment::class, 'shift_rotation_id');
    }
}
