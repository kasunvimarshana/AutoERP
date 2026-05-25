<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemBrandModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifierModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;

class ItemModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, HasActiveScope, SoftDeletes;

    protected $table = 'items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allow_auto_purchase_order' => 'boolean',
            'auto_replenishment_enabled' => 'boolean',
            'cost_price' => 'decimal:4',
            'estimated_service_time_hours' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'is_active' => 'boolean',
            'is_batch_tracked' => 'boolean',
            'is_lot_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
            'is_stockable' => 'boolean',
            'lead_time_days' => 'integer',
            'maximum_stock' => 'decimal:4',
            'metadata' => 'array',
            'minimum_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'review_period_days' => 'integer',
            'row_version' => 'integer',
            'safety_stock' => 'decimal:4',
            'sales_price' => 'decimal:4',
            'standard_cost' => 'decimal:4',
        ];
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ItemBrandModel::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategoryModel::class, 'category_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'cogs_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'expense_account_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'income_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'inventory_account_id');
    }

    public function inventoryGainAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'inventory_gain_account_id');
    }

    public function inventoryLossAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'inventory_loss_account_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function priceVarianceAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'price_variance_account_id');
    }

    public function purchaseReturnAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'purchase_return_account_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'purchase_uom_id');
    }

    public function salesReturnAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'sales_return_account_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'sales_uom_id');
    }

    public function stockTransferAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'stock_transfer_account_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function wipAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'wip_account_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BatchModel::class, 'item_id');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboItemModel::class, 'combo_item_id');
    }

    public function cycleCountLines(): HasMany
    {
        return $this->hasMany(CycleCountLineModel::class, 'item_id');
    }

    public function gdnLines(): HasMany
    {
        return $this->hasMany(GdnLineModel::class, 'item_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'item_id');
    }

    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayerModel::class, 'item_id');
    }

    public function itemIdentifiers(): HasMany
    {
        return $this->hasMany(ItemIdentifierModel::class, 'item_id');
    }

    public function itemVariantAttributes(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeModel::class, 'item_id');
    }

    public function itemVariants(): HasMany
    {
        return $this->hasMany(ItemVariantModel::class, 'item_id');
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItemModel::class, 'item_id');
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'item_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'item_id');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'item_id');
    }

    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'item_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(SerialModel::class, 'item_id');
    }

    public function stockAdjustmentLines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLineModel::class, 'item_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevelModel::class, 'item_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovementModel::class, 'item_id');
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservationModel::class, 'item_id');
    }

    public function stockTransferLines(): HasMany
    {
        return $this->hasMany(StockTransferLineModel::class, 'item_id');
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItemModel::class, 'item_id');
    }

    public function transferOrderLines(): HasMany
    {
        return $this->hasMany(TransferOrderLineModel::class, 'item_id');
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(UomConversionModel::class, 'item_id');
    }

    public function valuationConfigs(): HasMany
    {
        return $this->hasMany(ValuationConfigModel::class, 'item_id');
    }

    public function vehicleServiceJobCardLines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardLineModel::class, 'item_id');
    }

    public function vehicleServiceLaborItems(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborItemModel::class, 'item_id');
    }

}
