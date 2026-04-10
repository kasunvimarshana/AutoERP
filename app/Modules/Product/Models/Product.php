<?php

namespace App\Modules\Product\Models;

use App\Modules\Common\Traits\UuidTrait;
use App\Modules\Common\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use UuidTrait, AuditableTrait;

    protected $fillable = [
        'type', 'sku', 'name', 'description', 'is_variable', 'parent_id',
        'uom_id', 'valuation_method', 'stock_rotation_strategy', 'allocation_algorithm',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'is_variable' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function productUoms()
    {
        return $this->hasMany(ProductUom::class);
    }

    public function bundles()
    {
        return $this->hasMany(ProductBundle::class, 'combo_product_id');
    }

    public function componentOf()
    {
        return $this->hasMany(ProductBundle::class, 'component_product_id');
    }
}