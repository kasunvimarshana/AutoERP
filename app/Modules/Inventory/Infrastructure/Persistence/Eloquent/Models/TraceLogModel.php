<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TraceLogModel extends Model
{
    protected $table = 'trace_logs';
    protected $guarded = [];
}
