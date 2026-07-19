<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalProrationRule;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalAgreement extends TenantOwnedModel
{
    use ScopesRentalContext;
    use SoftDeletes;

    protected $table = 'rental_agreements';
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(static function (RentalAgreement $agreement): void {
            $agreement->assertPartyShape();
        });

        static::deleting(static function (RentalAgreement $agreement): void {
            $status = $agreement->status instanceof RentalAgreementStatus
                ? $agreement->status
                : RentalAgreementStatus::from((string) $agreement->status);

            if ($status !== RentalAgreementStatus::Draft) {
                throw new LogicException('Only draft rental agreements can be deleted. Use governed cancellation, completion, or termination for contract history.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'row_version' => 'integer', 'tenant_id' => 'integer', 'organization_unit_id' => 'integer',
            'agreement_kind' => RentalAgreementKind::class, 'reservation_id' => 'integer', 'customer_id' => 'integer', 'supplier_id' => 'integer',
            'agreement_date' => 'date', 'executed_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'actual_ended_at' => 'datetime',
            'rental_mode' => RentalMode::class, 'billing_cycle' => RentalBillingCycle::class,
            'billing_basis' => RentalBillingBasis::class, 'proration_rule' => RentalProrationRule::class,
            'currency_id' => 'integer', 'status' => RentalAgreementStatus::class, 'metadata' => 'array',
            'approved_at' => 'datetime', 'terminated_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(TenantModel::class, 'tenant_id'); }
    public function organizationUnit(): BelongsTo { return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id'); }
    public function reservation(): BelongsTo { return $this->belongsTo(RentalReservation::class, 'reservation_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function terms(): HasMany { return $this->hasMany(RentalAgreementTerm::class, 'agreement_id')->orderBy('sequence'); }
    public function rateVersions(): HasMany { return $this->hasMany(RentalAgreementRateVersion::class, 'agreement_id'); }
    public function activeRateVersion(): HasOne { return $this->hasOne(RentalAgreementRateVersion::class, 'agreement_id')->where('status', 'active')->latestOfMany('version_number'); }
    public function allocations(): HasMany { return $this->hasMany(RentalVehicleAllocation::class, 'agreement_id'); }
    public function driverAssignments(): HasMany { return $this->hasMany(RentalDriverAssignment::class, 'agreement_id'); }
    public function billingPeriods(): HasMany { return $this->hasMany(RentalBillingPeriod::class, 'agreement_id'); }
    public function expenses(): HasMany { return $this->hasMany(RentalExpense::class, 'agreement_id'); }
    public function depositRequirement(): HasOne { return $this->hasOne(RentalDepositRequirement::class, 'agreement_id'); }

    private function assertPartyShape(): void
    {
        $kind = $this->agreement_kind instanceof RentalAgreementKind
            ? $this->agreement_kind
            : RentalAgreementKind::from((string) $this->agreement_kind);

        if ($kind === RentalAgreementKind::CustomerRental
            && ($this->customer_id === null || $this->supplier_id !== null)) {
            throw new LogicException('Customer rental agreement requires only a customer.');
        }

        if ($kind === RentalAgreementKind::OwnerSupply
            && ($this->supplier_id === null || $this->customer_id !== null)) {
            throw new LogicException('Owner supply agreement requires only a supplier/vehicle owner.');
        }
    }
}
