<?php

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    public function vehicles()
    {
        return $this->belongsToMany(VehicleModel::class, 'supplier_vehicles', 'customer_id', 'vehicle_id');
    }
}
