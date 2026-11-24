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

final class HrBiometricDeviceUser extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_biometric_device_users';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'device_id' => 'integer',
            'employee_id' => 'integer',
            'is_active' => 'boolean',
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
}
