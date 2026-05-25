<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class GrnLineModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, SoftDeletes;

    protected $table = 'grn_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'expected_qty' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'line_total' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'metadata' => 'array',
            'received_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function grnHeader(): BelongsTo
    {
        return $this->belongsTo(GrnHeaderModel::class, 'grn_header_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLineModel::class, 'purchase_order_line_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariantModel::class, 'variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'original_grn_line_id');
    }

}
