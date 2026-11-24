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

final class HrHolidayCalendar extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_holiday_calendars';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'calendar_year' => 'integer',
            'is_default' => 'boolean',
        ]);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(HrHoliday::class, 'calendar_id');
    }
}
