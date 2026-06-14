<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Enums\RentalType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalReservation extends CoreModel
{
    use ScopesRentalContext;
    use SoftDeletes;

    protected $table = 'rental_reservations';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'direction' => RentalAgreementDirection::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
            'rental_type' => RentalType::class,
            'vehicle_id' => 'integer',
            'start_at' => 'datetime',
            'expected_end_at' => 'datetime',
            'currency_id' => 'integer',
            'status' => RentalReservationStatus::class,
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(RentalAgreement::class, 'reservation_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RentalStatusHistory::class, 'reservation_id')->latest('changed_at');
    }
}
