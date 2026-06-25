<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Models\HrEmployee;

final class VehicleServiceInspection extends CoreModel
{
    protected $table = 'vehicle_service_inspections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'odometer_reading' => 'decimal:6',
            'inspected_by' => 'integer',
            'inspected_at' => 'datetime',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'inspected_by');
    }
}
