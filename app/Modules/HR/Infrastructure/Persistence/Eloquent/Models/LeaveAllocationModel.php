<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAllocationModel extends Model
{
    protected $table = 'leave_allocations';
    protected $guarded = [];
}
