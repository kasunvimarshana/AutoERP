<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class Customer extends CoreModel
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_type' => CustomerType::class,
            'status' => CustomerStatus::class,
            'default_currency_id' => 'integer',
            'payment_term_id' => 'integer',
            'credit_limit' => 'decimal:6',
            'opening_balance' => 'decimal:6',
            'is_credit_allowed' => 'boolean',
            'is_advance_allowed' => 'boolean',
            'is_tax_exempt' => 'boolean',
            'marketing_consent' => 'boolean',
            'preferred_communication_channel' => PreferredCommunicationChannel::class,
            'metadata' => 'array',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'default_currency_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CustomerBankAccount::class, 'customer_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerCategory::class,
            'customer_category_assignments',
            'customer_id',
            'customer_category_id',
        )->withPivot(['tenant_id', 'organization_unit_id'])->withTimestamps();
    }

    public function categoryAssignments(): HasMany
    {
        return $this->hasMany(CustomerCategoryAssignment::class, 'customer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class, 'customer_id');
    }

    public function creditProfile(): HasOne
    {
        return $this->hasOne(CustomerCreditProfile::class, 'customer_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CustomerStatusHistory::class, 'customer_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class, 'customer_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CustomerStatus::Active);
    }

    public function scopeForTenant(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
    }
}
