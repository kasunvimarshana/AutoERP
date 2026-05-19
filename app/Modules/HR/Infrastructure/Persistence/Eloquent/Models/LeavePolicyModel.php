<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class LeavePolicyModel extends Model
{
    protected $table = 'leave_policies';
    protected $guarded = [];
}
