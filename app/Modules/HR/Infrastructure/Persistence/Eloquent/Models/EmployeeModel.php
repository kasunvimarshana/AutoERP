<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;
use Illuminate\Database\Eloquent\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(DepartmentModel::class, 'user_id', 'id');
    }

    public function designation()
    {
        return $this->belongsTo(DesignationModel::class, 'user_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(EmploymentTypeModel::class, 'user_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(VehicleServiceJobCardModel::class, 'assigned_to', 'id');
    }

    public function laborItems()
    {
        return $this->belongsToMany(VehicleServiceLaborAssignmentModel::class, 'vehicle_service_labor_assignments', 'employee_id', 'labor_item_id');
    }
}
