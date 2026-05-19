<?php

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerVehicleModel extends Model
{
    protected $table = 'customer_vehicles';
    protected $guarded = [];
}
