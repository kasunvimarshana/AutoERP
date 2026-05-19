<?php

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnModel extends Model
{
    protected $table = 'purchase_returns';
    protected $guarded = [];
}
