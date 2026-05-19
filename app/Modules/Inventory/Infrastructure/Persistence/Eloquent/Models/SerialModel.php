<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SerialModel extends Model
{
    protected $table = 'serials';
    protected $guarded = [];
}
