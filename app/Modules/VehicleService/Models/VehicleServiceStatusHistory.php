<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleService\Enums\VehicleServiceLifecycleDimension;

final class VehicleServiceStatusHistory extends TenantOwnedModel
{
    protected $table = 'vehicle_service_status_histories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'dimension' => VehicleServiceLifecycleDimension::class,
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }
}
