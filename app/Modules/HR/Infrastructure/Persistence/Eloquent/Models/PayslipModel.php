<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipModel extends Model
{
    protected $table = 'payslips';
    protected $guarded = [];
}
