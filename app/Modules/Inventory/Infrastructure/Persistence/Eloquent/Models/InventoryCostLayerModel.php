<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class InventoryCostLayerModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'inventory_cost_layers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
            'layer_date' => 'date',
            'metadata' => 'array',
            'quantity_in' => 'decimal:4',
            'quantity_remaining' => 'decimal:4',
            'row_version' => 'integer',
            'source_transaction_id' => 'integer',
            'total_cost' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
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

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariantModel::class, 'variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

}
