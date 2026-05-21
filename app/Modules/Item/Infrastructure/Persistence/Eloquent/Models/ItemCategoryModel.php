<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategoryModel extends Model
{
    protected $table = 'item_categories';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ItemCategoryModel::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ItemCategoryModel::class, 'parent_id');
    }
}
