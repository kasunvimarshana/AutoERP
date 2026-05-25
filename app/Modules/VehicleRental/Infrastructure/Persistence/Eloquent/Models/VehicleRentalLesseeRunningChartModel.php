<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;

class VehicleRentalLesseeRunningChartModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'vehicle_rental_lessee_running_charts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credit_note_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'driver_hours_double_ot' => 'decimal:4',
            'driver_hours_normal' => 'decimal:4',
            'driver_hours_ot' => 'decimal:4',
            'end_date' => 'date',
            'end_km' => 'decimal:4',
            'end_mileage' => 'decimal:4',
            'garage_mileage' => 'decimal:4',
            'hours_used' => 'decimal:4',
            'km_reading' => 'decimal:4',
            'km_travelled' => 'decimal:4',
            'log_date' => 'date',
            'metadata' => 'array',
            'night_outs' => 'integer',
            'other_charges' => 'decimal:4',
            'row_version' => 'integer',
            'start_date' => 'date',
            'start_km' => 'decimal:4',
            'start_mileage' => 'decimal:4',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'driver_id');
    }

    public function lesseeAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleRentalLesseeAgreementModel::class, 'lessee_agreement_id');
    }

    public function lessorAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleRentalLessorAgreementModel::class, 'lessor_agreement_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

}
