<?php

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPriceListModel extends Model
{
    protected $table = 'customer_price_lists';
    protected $guarded = [];
}
