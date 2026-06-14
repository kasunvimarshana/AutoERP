<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementVehicle extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_agreement_vehicles';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_party_type' => RentalPartyType::class,
            'owner_party_id' => 'integer',
            'allocated_from' => 'datetime',
            'allocated_to' => 'datetime',
            'start_odometer' => 'decimal:6',
            'end_odometer' => 'decimal:6',
            'status' => RentalAgreementVehicleStatus::class,
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function pickupInspection(): HasOne
    {
        return $this->hasOne(RentalPickupInspection::class, 'agreement_vehicle_id');
    }

    public function returnInspection(): HasOne
    {
        return $this->hasOne(RentalReturnInspection::class, 'agreement_vehicle_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(RentalUsageLog::class, 'agreement_vehicle_id');
    }
}
