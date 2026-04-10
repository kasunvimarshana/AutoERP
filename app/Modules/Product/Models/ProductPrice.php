<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductPrice extends BaseModel
{
    protected $table = 'product_prices';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'price_list_id',
        'uom_id',
        'currency_id',
        'price',
        'min_qty',
        'valid_from',
        'valid_to',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'min_qty' => 'decimal:4',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\PriceList::class, 'price_list_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }
}
