<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnLineModel extends Model
{
    protected $table = 'purchase_return_lines';
    protected $guarded = [];
}
