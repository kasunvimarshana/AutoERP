<?php

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListModel extends Model
{
    protected $table = 'price_lists';
    protected $guarded = [];
}
