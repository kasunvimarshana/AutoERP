<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalPickupInspection extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_pickup_inspections';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'agreement_vehicle_id' => 'integer',
            'vehicle_id' => 'integer',
            'inspected_at' => 'datetime',
            'odometer' => 'decimal:6',
            'fuel_level' => 'decimal:6',
            'attachments' => 'array',
            'inspected_by' => 'integer',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function agreementVehicle(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicle::class, 'agreement_vehicle_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
