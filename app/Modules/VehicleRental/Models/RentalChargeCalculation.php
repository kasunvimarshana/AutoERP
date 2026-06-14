<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalChargeCalculationType;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalChargeCalculation extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_charge_calculations';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'agreement_vehicle_id' => 'integer',
            'usage_log_id' => 'integer',
            'usage_context_id' => 'integer',
            'rate_snapshot_id' => 'integer',
            'agreement_direction' => RentalAgreementDirection::class,
            'financial_side' => RentalFinancialSide::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
            'source_id' => 'integer',
            'calculation_type' => RentalChargeCalculationType::class,
            'measured_quantity' => 'decimal:6',
            'allowed_quantity' => 'decimal:6',
            'chargeable_quantity' => 'decimal:6',
            'rate' => 'decimal:6',
            'multiplier' => 'decimal:6',
            'amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'withholding_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'calculation_version' => 'integer',
            'supersedes_calculation_id' => 'integer',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }

    public function usageContext(): BelongsTo
    {
        return $this->belongsTo(RentalUsageContext::class, 'usage_context_id');
    }

    public function rateSnapshot(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementRateSnapshot::class, 'rate_snapshot_id');
    }

    public function charge(): HasOne
    {
        return $this->hasOne(RentalCharge::class, 'charge_calculation_id');
    }

    public function supersededCalculation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_calculation_id');
    }
}
