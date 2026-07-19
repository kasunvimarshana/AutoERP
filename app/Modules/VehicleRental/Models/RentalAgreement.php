<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;

final class RentalAgreement extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_agreements';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'kind' => RentalAgreementKind::class,
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'executed_at' => 'date',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'billing_basis' => RentalBillingBasis::class,
            'currency_id' => 'integer',
            'tax_group_id' => 'integer',
            'included_km' => 'decimal:6',
            'deposit_required' => 'boolean',
            'deposit_amount' => 'decimal:6',
            'payment_terms_days' => 'integer',
            'status' => RentalAgreementStatus::class,
            'created_by' => 'integer',
            'activated_by' => 'integer',
            'activated_at' => 'datetime',
            'closed_by' => 'integer',
            'closed_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }


    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'tax_group_id');
    }

    public function rateVersions(): HasMany
    {
        return $this->hasMany(RentalRateVersion::class, 'agreement_id')->orderByDesc('version_number');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RentalAssignment::class, 'agreement_id')->orderByDesc('starts_at');
    }
}
