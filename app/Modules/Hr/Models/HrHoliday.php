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

final class HrHoliday extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_holidays';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'calendar_id' => 'integer',
            'holiday_date' => 'date',
            'is_mandatory' => 'boolean',
            'is_comp_off_eligible' => 'boolean',
            'overtime_multiplier' => 'decimal:2',
        ]);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(HrHolidayCalendar::class, 'calendar_id');
    }
}
