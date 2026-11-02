<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Enums\RentalReservationStatus;

final class RentalReservation extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_reservations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'customer_id' => 'integer',
            'requested_vehicle_id' => 'integer',
            'requested_vehicle_category_id' => 'integer',
            'rental_mode' => DriverMode::class,
            'billing_cycle' => RentalBasis::class,
            'requested_start_at' => 'datetime',
            'requested_end_at' => 'datetime',
            'currency_id' => 'integer',
            'estimated_amount' => 'decimal:6',
            'estimated_deposit_amount' => 'decimal:6',
            'status' => RentalReservationStatus::class,
            'converted_agreement_id' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)
            ->when($organizationUnitId !== null, fn (Builder $scope) => $scope->where('organization_unit_id', $organizationUnitId));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function requestedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'requested_vehicle_id')->withTrashed();
    }

    public function requestedVehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'requested_vehicle_category_id')->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function convertedAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class, 'converted_agreement_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(RentalReservationHistory::class, 'reservation_id')->orderBy('id');
    }
}
