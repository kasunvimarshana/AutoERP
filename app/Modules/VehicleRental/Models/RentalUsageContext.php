<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalUsageContext extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_usage_contexts';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'usage_log_id' => 'integer',
            'agreement_id' => 'integer',
            'agreement_vehicle_id' => 'integer',
            'agreement_vehicle_link_id' => 'integer',
            'rate_snapshot_id' => 'integer',
            'agreement_direction' => RentalAgreementDirection::class,
            'financial_side' => RentalFinancialSide::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
        ];
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicle::class, 'agreement_vehicle_id');
    }

    public function agreementVehicleLink(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicleLink::class, 'agreement_vehicle_link_id');
    }

    public function rateSnapshot(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementRateSnapshot::class, 'rate_snapshot_id');
    }
}
