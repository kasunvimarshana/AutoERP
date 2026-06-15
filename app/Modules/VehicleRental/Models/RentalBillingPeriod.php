<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalBillingPeriod extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_billing_periods';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'rate_snapshot_id' => 'integer',
            'agreement_direction' => RentalAgreementDirection::class,
            'financial_side' => RentalFinancialSide::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'period_sequence' => 'integer',
            'billing_cycle' => RentalBillingCycle::class,
            'billing_basis' => RentalBillingBasis::class,
            'is_final' => 'boolean',
            'closed_at' => 'datetime',
            'closed_by' => 'integer',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function rateSnapshot(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementRateSnapshot::class, 'rate_snapshot_id');
    }

    public function chargeRuns(): HasMany
    {
        return $this->hasMany(RentalChargeRun::class, 'billing_period_id');
    }
}
