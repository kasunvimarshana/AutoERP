<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplicationModel extends Model
{
    protected $table = 'leave_applications';
    protected $guarded = [];
}
