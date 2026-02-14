<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StockLocation Model
 * 
 * Represents a storage location within a warehouse
 */
class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'aisle',
        'rack',
        'shelf',
        'bin',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the warehouse that owns this location
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get stock ledger entries for this location
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'location_id');
    }
}
