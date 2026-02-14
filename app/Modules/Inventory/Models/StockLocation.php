<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Location Model
 *
 * Detailed bin/shelf locations within warehouses.
 */
class StockLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'location_code',
        'aisle',
        'rack',
        'shelf',
        'bin',
    ];

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get full location identifier.
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->aisle ? "A:{$this->aisle}" : null,
            $this->rack ? "R:{$this->rack}" : null,
            $this->shelf ? "S:{$this->shelf}" : null,
            $this->bin ? "B:{$this->bin}" : null,
        ]);

        return implode('-', $parts) ?: $this->location_code;
    }
}
