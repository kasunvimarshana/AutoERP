<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreementVehicleLink extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_agreement_vehicle_links';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'inbound_agreement_id' => 'integer',
            'inbound_agreement_vehicle_id' => 'integer',
            'outbound_agreement_id' => 'integer',
            'outbound_agreement_vehicle_id' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'status' => RentalAgreementVehicleLinkStatus::class,
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function inboundAgreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'inbound_agreement_id');
    }

    public function inboundAllocation(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicle::class, 'inbound_agreement_vehicle_id');
    }

    public function outboundAgreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'outbound_agreement_id');
    }

    public function outboundAllocation(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicle::class, 'outbound_agreement_vehicle_id');
    }

    public function usageContexts(): HasMany
    {
        return $this->hasMany(RentalUsageContext::class, 'agreement_vehicle_link_id');
    }
}
