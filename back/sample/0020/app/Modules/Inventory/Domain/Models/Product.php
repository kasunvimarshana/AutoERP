<?php

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Core\Domain\Models\BaseModel;

class Product extends BaseModel {
    protected $fillable = ['tenant_id', 'sku', 'name', 'uom'];
}
