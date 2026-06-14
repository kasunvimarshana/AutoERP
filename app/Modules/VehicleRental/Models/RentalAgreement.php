<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Supplier\Models\Supplier;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreement extends CoreModel
{
    use ScopesRentalContext;
    use SoftDeletes;

    protected $table = 'rental_agreements';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'reservation_id' => 'integer',
            'direction' => RentalAgreementDirection::class,
            'party_type' => RentalPartyType::class,
            'party_id' => 'integer',
            'rental_type' => RentalType::class,
            'billing_cycle' => RentalBillingCycle::class,
            'agreement_date' => 'date',
            'start_at' => 'datetime',
            'expected_end_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'currency_id' => 'integer',
            'status' => RentalAgreementStatus::class,
            'terms_snapshot' => 'array',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'reservation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(RentalAgreementVehicle::class, 'agreement_id')->orderBy('allocated_from');
    }

    public function rateSnapshot(): HasOne
    {
        return $this->hasOne(RentalAgreementRateSnapshot::class, 'agreement_id');
    }

    public function pickupInspections(): HasMany
    {
        return $this->hasMany(RentalPickupInspection::class, 'agreement_id');
    }

    public function returnInspections(): HasMany
    {
        return $this->hasMany(RentalReturnInspection::class, 'agreement_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(RentalUsageLog::class, 'agreement_id')->orderBy('usage_date');
    }

    public function operationalUsageLogs(): BelongsToMany
    {
        return $this->belongsToMany(
            RentalUsageLog::class,
            'rental_usage_contexts',
            'agreement_id',
            'usage_log_id',
        )->withPivot([
            'agreement_vehicle_id',
            'agreement_vehicle_link_id',
            'rate_snapshot_id',
            'agreement_direction',
            'financial_side',
            'party_type',
            'party_id',
        ])->withTimestamps()->orderBy('usage_date');
    }

    public function usageContexts(): HasMany
    {
        return $this->hasMany(RentalUsageContext::class, 'agreement_id');
    }

    public function inboundVehicleLinks(): HasMany
    {
        return $this->hasMany(RentalAgreementVehicleLink::class, 'inbound_agreement_id');
    }

    public function outboundVehicleLinks(): HasMany
    {
        return $this->hasMany(RentalAgreementVehicleLink::class, 'outbound_agreement_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(RentalExpense::class, 'agreement_id');
    }

    public function chargeCalculations(): HasMany
    {
        return $this->hasMany(RentalChargeCalculation::class, 'agreement_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(RentalCharge::class, 'agreement_id');
    }

    public function invoiceLinks(): HasMany
    {
        return $this->hasMany(RentalInvoiceLink::class, 'agreement_id');
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(RentalPaymentLink::class, 'agreement_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RentalStatusHistory::class, 'agreement_id')->latest('changed_at');
    }
}
