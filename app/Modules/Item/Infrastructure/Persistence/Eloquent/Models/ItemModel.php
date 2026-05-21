<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UOMConversionModel;

class ItemModel extends Model
{
    protected $table = 'items';
    protected $guarded = [];

    public function comboItems()
    {
        return $this->hasMany(ComboItemModel::class, 'combo_item_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ItemCategoryModel::class, 'category_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(ItemBrandModel::class, 'brand_id', 'id');
    }

    public function baseUom()
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id', 'id');
    }

    public function purchaseUom()
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'purchase_uom_id', 'id');
    }

    public function salesUom()
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'sales_uom_id', 'id');
    }

    public function componentItems()
    {
        return $this->belongsToMany(
            ItemModel::class,
            'combo_items',
            'combo_item_id',
            'component_item_id'
        );
        // ->withPivot([
        //     'quantity',
        //     'uom_id',
        //     'standard_cost',
        //     'cost_price',
        //     'sales_price',
        //     'incentive_type',
        //     'incentive_value',
        //     'sort_order'
        // ])
        // ->withTimestamps();
    }

    public function uomConversions()
    {
        return $this->hasMany(UOMConversionModel::class, 'item_id', 'id');
    }
}
