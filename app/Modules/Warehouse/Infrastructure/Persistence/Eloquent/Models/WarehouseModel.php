<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PickingTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PutAwayTaskModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class WarehouseModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'warehouses';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function cycleCountHeaders(): HasMany
    {
        return $this->hasMany(CycleCountHeaderModel::class, 'warehouse_id');
    }

    public function gdnHeaders(): HasMany
    {
        return $this->hasMany(GdnHeaderModel::class, 'warehouse_id');
    }

    public function gdnLines(): HasMany
    {
        return $this->hasMany(GdnLineModel::class, 'warehouse_id');
    }

    public function grnHeaders(): HasMany
    {
        return $this->hasMany(GrnHeaderModel::class, 'warehouse_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'warehouse_id');
    }

    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayerModel::class, 'warehouse_id');
    }

    public function pickingTasks(): HasMany
    {
        return $this->hasMany(PickingTaskModel::class, 'source_warehouse_id');
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItemModel::class, 'warehouse_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrderModel::class, 'warehouse_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'warehouse_id');
    }

    public function putAwayTasks(): HasMany
    {
        return $this->hasMany(PutAwayTaskModel::class, 'target_warehouse_id');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'warehouse_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrderModel::class, 'warehouse_id');
    }

    public function salesReturnLines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'warehouse_id');
    }

    public function stockAdjustmentLines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLineModel::class, 'warehouse_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustmentModel::class, 'warehouse_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevelModel::class, 'warehouse_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovementModel::class, 'warehouse_id');
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservationModel::class, 'warehouse_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransferModel::class, 'to_warehouse_id');
    }

    public function traceLogs(): HasMany
    {
        return $this->hasMany(TraceLogModel::class, 'destination_warehouse_id');
    }

    public function transferOrders(): HasMany
    {
        return $this->hasMany(TransferOrderModel::class, 'to_warehouse_id');
    }

    public function valuationConfigs(): HasMany
    {
        return $this->hasMany(ValuationConfigModel::class, 'warehouse_id');
    }

    public function vehicleServiceJobCardLines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardLineModel::class, 'warehouse_id');
    }

    public function vehicleServiceJobCards(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'warehouse_id');
    }

    public function warehouseLocations(): HasMany
    {
        return $this->hasMany(WarehouseLocationModel::class, 'warehouse_id');
    }

}
