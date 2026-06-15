<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalChargeRun extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_charge_runs';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'billing_period_id' => 'integer',
            'agreement_id' => 'integer',
            'rate_snapshot_id' => 'integer',
            'agreement_direction' => RentalAgreementDirection::class,
            'financial_side' => RentalFinancialSide::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
            'billing_period_start' => 'datetime',
            'billing_period_end' => 'datetime',
            'period_sequence' => 'integer',
            'run_version' => 'integer',
            'invoice_status' => RentalChargeInvoiceStatus::class,
            'amount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'withholding_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(RentalBillingPeriod::class, 'billing_period_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function rateSnapshot(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementRateSnapshot::class, 'rate_snapshot_id');
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(RentalChargeCalculation::class, 'charge_run_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(RentalCharge::class, 'charge_run_id');
    }
}
