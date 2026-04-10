<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id',
        'movement_number',
        'movement_type',
        'source_type',
        'source_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'to_location_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'journal_entry_id',
        'notes',
        'created_by',
        'moved_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'moved_at' => 'datetime'
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\Batch::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\SerialNumber::class, 'serial_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }
}
