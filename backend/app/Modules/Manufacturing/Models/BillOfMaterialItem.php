<?php

namespace App\Modules\Manufacturing\Models;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialItem extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Manufacturing\BillOfMaterialItemFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'bill_of_material_id',
        'component_product_id',
        'quantity',
        'uom_id',
        'scrap_factor',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'scrap_factor' => 'decimal:2',
    ];

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    /**
     * Calculate actual quantity needed including scrap factor
     */
    public function getActualQuantityNeeded(float $productionQuantity): float
    {
        $baseQuantity = $this->quantity * $productionQuantity;
        $scrapAmount = $baseQuantity * ($this->scrap_factor / 100);

        return $baseQuantity + $scrapAmount;
    }
}
