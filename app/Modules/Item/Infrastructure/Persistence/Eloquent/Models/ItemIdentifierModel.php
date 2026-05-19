<?php

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ItemIdentifierModel extends Model
{
    protected $table = 'item_identifiers';
    protected $guarded = [];
}
