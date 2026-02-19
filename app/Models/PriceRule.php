<?php

namespace App\Models;

use App\Enums\PricingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'price_list_id', 'product_id', 'variant_id',
        'pricing_type', 'value', 'min_quantity', 'max_quantity',
        'tiers', 'conditions', 'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pricing_type' => PricingType::class,
            'value' => 'string',
            'min_quantity' => 'string',
            'max_quantity' => 'string',
            'tiers' => 'array',
            'conditions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
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
