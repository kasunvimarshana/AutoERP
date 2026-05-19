<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAttributeModel extends Model
{
    protected $table = 'item_attributes';
    protected $guarded = [];
}
