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
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrBiometricDevice extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_biometric_devices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'port' => 'integer',
            'protocol' => BiometricProtocol::class,
            'status' => BiometricDeviceStatus::class,
            'is_enabled' => 'boolean',
            'auto_map_users' => 'boolean',
            'last_ping_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'total_punches_synced' => 'integer',
            'settings' => 'array',
            'row_version' => 'integer',
        ]);
    }

    public function deviceUsers(): HasMany
    {
        return $this->hasMany(HrBiometricDeviceUser::class, 'device_id');
    }

    public function rawPunches(): HasMany
    {
        return $this->hasMany(HrBiometricRawPunch::class, 'device_id');
    }
}
