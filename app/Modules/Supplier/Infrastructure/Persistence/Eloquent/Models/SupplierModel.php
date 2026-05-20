<?php

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;

class SupplierModel extends Model
{
    protected $table = 'suppliers';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    public function vehicles()
    {
        return $this->belongsToMany(VehicleModel::class, 'supplier_vehicles', 'supplier_id', 'vehicle_id');
    }
}
