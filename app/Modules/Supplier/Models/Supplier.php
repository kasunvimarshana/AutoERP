<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;
use Modules\Tenant\Models\TenantModel;

final class Supplier extends CoreModel
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_type' => SupplierType::class,
            'status' => SupplierStatus::class,
            'default_currency_id' => 'integer',
            'payment_term_id' => 'integer',
            'credit_limit' => 'decimal:6',
            'is_credit_allowed' => 'boolean',
            'is_advance_allowed' => 'boolean',
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
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class, 'supplier_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            SupplierCategory::class,
            'supplier_category_assignments',
            'supplier_id',
            'supplier_category_id',
        )->withPivot(['tenant_id', 'organization_unit_id'])->withTimestamps();
    }

    public function categoryAssignments(): HasMany
    {
        return $this->hasMany(SupplierCategoryAssignment::class, 'supplier_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class, 'supplier_id');
    }

    public function itemMappings(): HasMany
    {
        return $this->hasMany(SupplierItemMapping::class, 'supplier_id');
    }

    public function creditProfile(): HasOne
    {
        return $this->hasOne(SupplierCreditProfile::class, 'supplier_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SupplierStatusHistory::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SupplierStatus::Active);
    }

    public function scopeForTenant(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query
            : $query->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
    }
}
