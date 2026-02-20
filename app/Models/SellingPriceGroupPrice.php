<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellingPriceGroupPrice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'selling_price_group_id', 'product_id', 'product_variant_id', 'price', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'string', // string for BCMath precision
        ];
    }

    public function sellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
