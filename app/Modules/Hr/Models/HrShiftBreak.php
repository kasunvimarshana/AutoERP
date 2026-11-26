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

final class HrShiftBreak extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_breaks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'shift_id' => 'integer',
            'duration_minutes' => 'integer',
            'is_paid' => 'boolean',
            'is_mandatory' => 'boolean',
        ]);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }
}
