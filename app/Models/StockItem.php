<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'product_id', 'variant_id',
        'quantity_on_hand', 'quantity_reserved', 'quantity_available',
        'reorder_point', 'reorder_quantity', 'cost_per_unit', 'currency', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'string',
            'quantity_reserved' => 'string',
            'quantity_available' => 'string',
            'reorder_point' => 'string',
            'reorder_quantity' => 'string',
            'cost_per_unit' => 'string',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
