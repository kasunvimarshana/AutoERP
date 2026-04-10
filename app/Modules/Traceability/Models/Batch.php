<?php

namespace App\Modules\Traceability\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use UuidTrait;

    protected $fillable = ['batch_number', 'product_id', 'manufacturing_date', 'expiry_date', 'supplier_id', 'lot_number'];

    protected $dates = ['manufacturing_date', 'expiry_date'];

    public function product()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class);
    }
}