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

final class HrAttendanceKiosk extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_kiosks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'geofence_id' => 'integer',
            'rotation_seconds' => 'integer',
            'is_active' => 'boolean',
            'last_heartbeat_at' => 'datetime',
        ]);
    }

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceGeofence::class, 'geofence_id');
    }
}
