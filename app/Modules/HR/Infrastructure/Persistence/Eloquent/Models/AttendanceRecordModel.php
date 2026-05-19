<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecordModel extends Model
{
    protected $table = 'attendance_records';
    protected $guarded = [];
}
