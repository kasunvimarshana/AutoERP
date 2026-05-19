<?php

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UOMConversionModel extends Model
{
    protected $table = 'uom_conversions';
    protected $guarded = [];
}
