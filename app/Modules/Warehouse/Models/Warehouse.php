<?php

namespace App\Modules\Warehouse\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use UuidTrait;

    protected $fillable = ['code', 'name', 'address', 'is_active'];

    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}