<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;

class SupplierModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasStatusScope, SoftDeletes;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'ap_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BatchModel::class, 'supplier_id');
    }

    public function grnHeaders(): HasMany
    {
        return $this->hasMany(GrnHeaderModel::class, 'supplier_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'supplier_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturnModel::class, 'supplier_id');
    }

    public function supplierAddresses(): HasMany
    {
        return $this->hasMany(SupplierAddressModel::class, 'supplier_id');
    }

    public function supplierContacts(): HasMany
    {
        return $this->hasMany(SupplierContactModel::class, 'supplier_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItemModel::class, 'supplier_id');
    }

    public function supplierPriceLists(): HasMany
    {
        return $this->hasMany(SupplierPriceListModel::class, 'supplier_id');
    }

    public function supplierVehicles(): HasMany
    {
        return $this->hasMany(SupplierVehicleModel::class, 'supplier_id');
    }

    public function vehicleRentalLessorAgreements(): HasMany
    {
        return $this->hasMany(VehicleRentalLessorAgreementModel::class, 'lessor_id');
    }

}
