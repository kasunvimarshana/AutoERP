<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAttributeValueModel extends Model
{
    protected $table = 'item_attribute_values';
    protected $guarded = [];
}
