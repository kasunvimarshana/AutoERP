<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BiometricDeviceModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_biometric_devices';

    protected $fillable = ['tenant_id', 'name', 'code', 'device_type', 'ip_address', 'port', 'location', 'org_unit_id', 'status', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'metadata' => 'array', 'port' => 'integer'];

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }
}
