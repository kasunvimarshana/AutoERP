<?php

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddressModel extends Model
{
    protected $table = 'customer_addresses';
    protected $guarded = [];
}
