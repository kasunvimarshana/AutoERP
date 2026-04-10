<?php

namespace App\Modules\Product\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    use UuidTrait;

    protected $fillable = ['code', 'name', 'category', 'base_uom_id'];

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }

    public function productUoms()
    {
        return $this->hasMany(ProductUom::class);
    }
}