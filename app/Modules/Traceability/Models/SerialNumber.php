<?php

namespace App\Modules\Traceability\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    use UuidTrait;

    protected $fillable = ['serial', 'product_id', 'batch_id', 'current_status', 'location_id', 'last_movement_id'];

    public function product()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class);
    }
}