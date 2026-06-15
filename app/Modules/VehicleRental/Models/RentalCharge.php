<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalChargeStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCharge extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_charges';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'billing_period_id' => 'integer',
            'charge_run_id' => 'integer',
            'agreement_id' => 'integer',
            'charge_calculation_id' => 'integer',
            'financial_side' => RentalFinancialSide::class,
            'billing_period_start' => 'datetime',
            'billing_period_end' => 'datetime',
            'period_sequence' => 'integer',
            'quantity' => 'decimal:6',
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'withholding_amount' => 'decimal:6',
            'tax_group_id' => 'integer',
            'total_amount' => 'decimal:6',
            'invoice_status' => RentalChargeInvoiceStatus::class,
            'status' => RentalChargeStatus::class,
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(RentalBillingPeriod::class, 'billing_period_id');
    }

    public function chargeRun(): BelongsTo
    {
        return $this->belongsTo(RentalChargeRun::class, 'charge_run_id');
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(RentalChargeCalculation::class, 'charge_calculation_id');
    }

    public function invoiceLinks(): HasMany
    {
        return $this->hasMany(RentalInvoiceLink::class, 'charge_id');
    }
}
