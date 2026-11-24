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
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Enums\RawPunchSyncStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrBiometricRawPunch extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_biometric_raw_punches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'device_id' => 'integer',
            'employee_id' => 'integer',
            'attendance_punch_id' => 'integer',
            'punch_time' => 'datetime',
            'received_at' => 'datetime',
            'punch_state' => BiometricPunchState::class,
            'verify_method' => BiometricVerifyMethod::class,
            'sync_status' => RawPunchSyncStatus::class,
        ]);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(HrBiometricDevice::class, 'device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function attendancePunch(): BelongsTo
    {
        return $this->belongsTo(HrAttendancePunch::class, 'attendance_punch_id');
    }
}
