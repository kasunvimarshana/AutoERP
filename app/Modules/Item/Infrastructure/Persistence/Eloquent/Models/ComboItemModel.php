<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class ComboItemModel extends Model
{
    protected $table = 'combo_items';
    protected $guarded = [];

    public function comboItem()
    {
        return $this->belongsTo(ItemModel::class, 'combo_item_id', 'id');
    }

    public function componentItem()
    {
        return $this->belongsTo(ItemModel::class, 'component_item_id', 'id');
    }

    public function componentVariant()
    {
        return $this->belongsTo(ItemVariantModel::class, 'component_variant_id', 'id');
    }

    public function componentUom()
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id', 'id');
    }
}
