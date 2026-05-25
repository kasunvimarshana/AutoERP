<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicleModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeRunningChartModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorRunningChartModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;

class VehicleModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'current_odometer' => 'integer',
            'insurance_expiry' => 'date',
            'last_service_date' => 'date',
            'last_service_odometer' => 'integer',
            'metadata' => 'array',
            'next_service_due_date' => 'date',
            'next_service_due_odometer' => 'integer',
            'registration_expiry' => 'date',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleModel::class, 'vehicle_id');
    }

    public function supplierVehicles(): HasMany
    {
        return $this->hasMany(SupplierVehicleModel::class, 'vehicle_id');
    }

    public function vehicleDocuments(): HasMany
    {
        return $this->hasMany(VehicleDocumentModel::class, 'vehicle_id');
    }

    public function vehicleRentalLesseeAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeAgreementModel::class, 'vehicle_id');
    }

    public function vehicleRentalLesseeRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLesseeRunningChartModel::class, 'vehicle_id');
    }

    public function vehicleRentalLessorAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementModel::class, 'vehicle_id');
    }

    public function vehicleRentalLessorRunningCharts(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorRunningChartModel::class, 'vehicle_id');
    }

    public function vehicleServiceJobCards(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'vehicle_id');
    }

}
