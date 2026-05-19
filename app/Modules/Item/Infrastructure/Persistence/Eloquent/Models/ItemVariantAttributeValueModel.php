<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariantAttributeValueModel extends Model
{
    protected $table = 'item_variant_attribute_values';
    protected $guarded = [];
}
