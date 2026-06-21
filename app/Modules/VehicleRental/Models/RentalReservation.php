<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalReservation extends CoreModel
{
    use ScopesRentalContext;
    use SoftDeletes;

    protected $table = 'rental_reservations';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer', 'tenant_id' => 'integer', 'organization_unit_id' => 'integer',
            'customer_id' => 'integer', 'requested_vehicle_id' => 'integer', 'requested_vehicle_category_id' => 'integer',
            'rental_mode' => RentalMode::class, 'billing_cycle' => RentalBillingCycle::class,
            'requested_start_at' => 'datetime', 'requested_end_at' => 'datetime', 'currency_id' => 'integer',
            'estimated_amount' => 'decimal:6', 'estimated_deposit_amount' => 'decimal:6',
            'status' => RentalReservationStatus::class, 'metadata' => 'array',
            'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(TenantModel::class, 'tenant_id'); }
    public function organizationUnit(): BelongsTo { return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function requestedVehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'requested_vehicle_id'); }
    public function requestedVehicleCategory(): BelongsTo { return $this->belongsTo(VehicleCategory::class, 'requested_vehicle_category_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function agreement(): HasOne { return $this->hasOne(RentalAgreement::class, 'reservation_id'); }
}
