<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Models;

use App\Modules\Product\Models\Product;
use App\Modules\MasterData\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BOMItem Model
 * 
 * Represents a component/material required in a Bill of Materials
 */
class BOMItem extends Model
{
    use HasFactory;

    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id',
        'product_id',
        'quantity',
        'unit_id',
        'unit_cost',
        'total_cost',
        'scrap_percentage',
        'sequence',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'scrap_percentage' => 'decimal:2',
        'sequence' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the BOM this item belongs to
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the component product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit of measure
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Calculate required quantity including scrap
     */
    public function getRequiredQuantityAttribute(): float
    {
        $scrapMultiplier = 1 + ($this->scrap_percentage / 100);
        return $this->quantity * $scrapMultiplier;
    }

    /**
     * Calculate total cost including scrap
     */
    public function calculateTotalCost(): float
    {
        return $this->required_quantity * $this->unit_cost;
    }
}
