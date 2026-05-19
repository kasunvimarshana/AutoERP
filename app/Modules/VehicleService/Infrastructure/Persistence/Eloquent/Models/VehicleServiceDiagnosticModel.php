<?php

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleServiceDiagnosticModel extends Model
{
    protected $table = 'vehicle_service_diagnostics';
    protected $guarded = [];
}
