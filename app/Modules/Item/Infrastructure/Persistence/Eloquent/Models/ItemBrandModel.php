<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBrandModel extends Model
{
    protected $table = 'item_brands';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ItemBrandModel::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ItemBrandModel::class, 'parent_id');
    }
}
