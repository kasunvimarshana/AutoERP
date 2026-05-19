<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveTypeModel extends Model
{
    protected $table = 'leave_types';
    protected $guarded = [];
}
