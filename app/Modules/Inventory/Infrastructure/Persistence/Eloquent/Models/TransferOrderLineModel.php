<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class TransferOrderLineModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'transfer_order_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'batch_id' => 'integer',
            'from_location_id' => 'integer',
            'item_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'received_qty' => 'decimal:4',
            'requested_qty' => 'decimal:4',
            'row_version' => 'integer',
            'serial_id' => 'integer',
            'shipped_qty' => 'decimal:4',
            'tenant_id' => 'integer',
            'to_location_id' => 'integer',
            'transfer_order_id' => 'integer',
            'unit_cost' => 'decimal:4',
            'uom_id' => 'integer',
            'variant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrderModel::class, 'transfer_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariantModel::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'to_location_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
