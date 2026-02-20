<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'product_id', 'variant_id',
        'batch_number', 'lot_number', 'serial_number', 'expiry_date', 'valuation_method',
        'movement_type', 'quantity', 'cost_per_unit', 'reference_type',
        'reference_id', 'notes', 'user_id', 'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'string',
            'cost_per_unit' => 'string',
            'expiry_date' => 'date',
            'moved_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
