<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'purchase_id', 'product_id', 'product_variant_id', 'quantity_ordered',
        'quantity_received', 'unit_cost', 'discount_percent', 'discount_amount',
        'tax_percent', 'tax_amount', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'string',
            'quantity_received' => 'string',
            'unit_cost' => 'string',
            'discount_percent' => 'string',
            'discount_amount' => 'string',
            'tax_percent' => 'string',
            'tax_amount' => 'string',
            'line_total' => 'string',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
