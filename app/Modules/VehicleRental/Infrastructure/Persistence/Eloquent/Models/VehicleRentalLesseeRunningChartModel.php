<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class VehicleRentalLesseeRunningChartModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'vehicle_rental_lessee_running_charts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'credit_note_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'driver_hours_double_ot' => 'decimal:4',
            'driver_hours_normal' => 'decimal:4',
            'driver_hours_ot' => 'decimal:4',
            'driver_id' => 'integer',
            'end_km' => 'decimal:4',
            'garage_mileage' => 'decimal:4',
            'hours_used' => 'decimal:4',
            'km_travelled' => 'decimal:4',
            'lessee_agreement_id' => 'integer',
            'lessor_agreement_id' => 'integer',
            'log_date' => 'date',
            'metadata' => 'array',
            'night_outs' => 'integer',
            'organization_unit_id' => 'integer',
            'other_charges' => 'decimal:4',
            'row_version' => 'integer',
            'start_km' => 'decimal:4',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function lesseeAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleRentalLesseeAgreementModel::class, 'lessee_agreement_id');
    }

    public function lessorAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleRentalLessorAgreementModel::class, 'lessor_agreement_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'driver_id');
    }
}

