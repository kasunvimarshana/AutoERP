<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionLine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pos_transaction_id', 'product_id', 'product_variant_id', 'quantity',
        'unit_price', 'discount_percent', 'discount_amount', 'tax_percent',
        'tax_amount', 'line_total', 'modifiers',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'string',
            'unit_price' => 'string',
            'discount_percent' => 'string',
            'discount_amount' => 'string',
            'tax_percent' => 'string',
            'tax_amount' => 'string',
            'line_total' => 'string',
            'modifiers' => 'array',
        ];
    }

    public function posTransaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class);
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
