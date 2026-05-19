<?php

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleServiceNonInventoryItemModel extends Model
{
    protected $table = 'vehicle_service_non_inventory_items';
    protected $guarded = [];
}
