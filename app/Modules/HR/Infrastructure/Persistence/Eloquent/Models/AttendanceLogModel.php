<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLogModel extends Model
{
    protected $table = 'attendance_logs';
    protected $guarded = [];
}
