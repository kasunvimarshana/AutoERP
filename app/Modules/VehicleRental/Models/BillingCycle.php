<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\User\Models\UserModel;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\BillingCycleMode;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;

final class BillingCycle extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_billing_cycles';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'period_from' => 'immutable_date',
            'period_until' => 'immutable_date',
            'status' => BillingCycleStatus::class,
            'billing_mode' => BillingCycleMode::class,
            'finalization_mode' => BillingFinalizationMode::class,
            'base_rent_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'excess_km_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'overtime_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'night_out_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'tolls_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'additions_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'deductions_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'net_subtotal' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'tax_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'total_billed_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'total_paid_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'exchange_rate' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'finalized_at' => 'immutable_datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenant, int $organization): Builder
    {
        return $query->forTenant($tenant)->where('organization_unit_id', $organization);
    }

    public function customerAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class, 'customer_agreement_id');
    }

    public function ownerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class, 'owner_agreement_id');
    }

    public function customerBaseCharge(): BelongsTo
    {
        return $this->belongsTo(CustomerBaseCharge::class, 'customer_base_charge_id');
    }

    public function ownerBaseCharge(): BelongsTo
    {
        return $this->belongsTo(OwnerBaseCharge::class, 'owner_base_charge_id');
    }

    public function initialInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'initial_invoice_id');
    }

    public function finalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'final_invoice_id');
    }

    public function supplementaryInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'supplementary_invoice_id');
    }

    public function charts(): HasMany
    {
        return $this->hasMany(BillingCycleChart::class, 'billing_cycle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingCycleItem::class, 'billing_cycle_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'finalized_by');
    }
}
