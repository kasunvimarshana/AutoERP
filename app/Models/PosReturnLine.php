<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturnLine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pos_return_id', 'pos_transaction_line_id', 'product_id',
        'product_variant_id', 'quantity', 'unit_price', 'refund_amount', 'restock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'string',
            'unit_price' => 'string',
            'refund_amount' => 'string',
            'restock' => 'boolean',
        ];
    }

    public function posReturn(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class);
    }

    public function posTransactionLine(): BelongsTo
    {
        return $this->belongsTo(PosTransactionLine::class);
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
