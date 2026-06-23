<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SupplierItemMapping extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'supplier_item_mappings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'default_purchase_uom_id' => 'integer',
            'minimum_order_quantity' => 'decimal:6',
            'lead_time_days' => 'integer',
            'is_preferred' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function defaultPurchaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'default_purchase_uom_id');
    }
}
