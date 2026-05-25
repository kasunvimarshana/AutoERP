<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;

class PriceListModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasActiveScope;

    protected $table = 'price_lists';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
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

    public function customerPriceLists(): HasMany
    {
        return $this->hasMany(CustomerPriceListModel::class, 'price_list_id');
    }

    public function gdnHeaders(): HasMany
    {
        return $this->hasMany(GdnHeaderModel::class, 'price_list_id');
    }

    public function grnHeaders(): HasMany
    {
        return $this->hasMany(GrnHeaderModel::class, 'price_list_id');
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItemModel::class, 'price_list_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'price_list_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrderModel::class, 'price_list_id');
    }

    public function supplierPriceLists(): HasMany
    {
        return $this->hasMany(SupplierPriceListModel::class, 'price_list_id');
    }

    public function vehicleServiceJobCards(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'price_list_id');
    }

}
