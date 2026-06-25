<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class VehicleServiceLineEmployee extends TenantOwnedModel
{
    protected $table = 'vehicle_service_line_employees';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'vehicle_service_job_line_id' => 'integer',
            'employee_id' => 'integer',
            'assigned_hours' => 'decimal:6',
            'rate' => 'decimal:6',
            'commission_type' => VehicleServiceCommissionType::class,
            'commission_value' => 'decimal:6',
            'commission_amount' => 'decimal:6',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJobLine::class, 'vehicle_service_job_line_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
