<?php

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $table = 'vehicles';
    protected $guarded = [];


    public function suppliers()
    {
        return $this->belongsToMany(SupplierModel::class, 'supplier_vehicles', 'vehicle_id', 'supplier_id');
    }

    public function customers()
    {
        return $this->belongsToMany(CustomerModel::class, 'customer_vehicles', 'vehicle_id', 'customer_id');
    }

    public function currentSupplier()
    {
        return $this->suppliers()->where('is_current', true);
    }

    public function currentCustomer()
    {
        return $this->suppliers()->where('is_current', true);
    }
}
