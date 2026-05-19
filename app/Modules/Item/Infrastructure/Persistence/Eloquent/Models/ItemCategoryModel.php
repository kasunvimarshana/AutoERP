<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategoryModel extends Model
{
    protected $table = 'item_categories';
    protected $guarded = [];
}
