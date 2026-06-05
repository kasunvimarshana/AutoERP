<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomModel;

final class ItemModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'base_uom_id' => 'integer',
            'purchase_uom_id' => 'integer',
            'sales_uom_id' => 'integer',
            'track_inventory' => 'boolean',
            'is_stock_item' => 'boolean',
            'is_service_item' => 'boolean',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UomModel::class, 'base_uom_id')->withTrashed();
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(UomModel::class, 'purchase_uom_id')->withTrashed();
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UomModel::class, 'sales_uom_id')->withTrashed();
    }
}
